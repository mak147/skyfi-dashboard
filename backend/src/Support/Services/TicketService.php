<?php

declare(strict_types=1);
namespace SkyFi\Support\Services;

use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Shared\Exceptions\{NotFoundException, ValidationException};
use SkyFi\Support\Contracts\{
    TicketAssignmentRepositoryContract,
    TicketCommentRepositoryContract,
    TicketRepositoryContract,
    TicketServiceContract,
};
use SkyFi\Support\DomainModels\{SupportTicket, TicketComment};
use SkyFi\Support\DTOs\{
    AssignmentData,
    CreateCommentData,
    CreateTicketData,
    EscalationData,
    MergeTicketsData,
    SplitTicketData,
    TicketListFilters,
    UpdateTicketData,
};
use SkyFi\Support\Validators\{TicketValidator, TicketWorkflowValidator};

final class TicketService implements TicketServiceContract
{
    public function __construct(
        private readonly TicketRepositoryContract $tickets,
        private readonly TicketCommentRepositoryContract $comments,
        private readonly TicketAssignmentRepositoryContract $assignments,
        private readonly TicketValidator $validator,
        private readonly TicketWorkflowValidator $workflow,
        private readonly AuditLoggerContract $audit,
    ) {}
    public function list(TicketListFilters $filters): array
    {
        $this->tickets->processBreaches();
        return $this->tickets->list($filters);
    }
    public function get(int $id): array
    {
        $this->tickets->processBreaches();
        $ticket = $this->require($id);
        return [
            "ticket" => $ticket->toArray(),
            "context" => $this->tickets->context($id),
            "comments" => array_map(
                fn($x) => $x->toArray(),
                $this->comments->list($id),
            ),
            "timeline" => $this->tickets->timeline($id),
            "assignments" => $this->tickets->assignments($id),
        ];
    }
    public function create(CreateTicketData $data, int $actorId): SupportTicket
    {
        $this->validator->create($data);
        $errors = $this->tickets->validateContext($data->toArray());
        if ($errors) {
            throw new ValidationException($errors);
        }
        $policy = $this->tickets->findSlaPolicy(
            $data->categoryId,
            $data->priority,
        );
        if (!$policy) {
            throw new ValidationException([
                [
                    "code" => "sla_policy_missing",
                    "detail" =>
                        "No active SLA policy matches this category and priority.",
                ],
            ]);
        }
        return $this->tickets->transaction(function () use (
            $data,
            $actorId,
            $policy,
        ) {
            $ticket = $this->tickets->create(
                $data->toArray(),
                $actorId,
                $policy,
            );
            $this->tickets->history(
                $ticket->id(),
                "created",
                $actorId,
                "Ticket created.",
                null,
                "new",
            );
            $this->audit(
                $actorId,
                "support.ticket.created",
                $ticket->id(),
                null,
                $ticket->toArray(),
            );
            EventDispatcher::dispatch(
                "support.ticket.created",
                $ticket->toArray(),
            );
            return $ticket;
        });
    }
    public function update(
        int $id,
        UpdateTicketData $data,
        int $actorId,
    ): SupportTicket {
        $old = $this->require($id);
        $merged = [...$old->toArray(), ...$data->values];
        $errors = $this->tickets->validateContext($merged);
        if ($errors) {
            throw new ValidationException($errors);
        }
        $values = $data->values;
        if (isset($values["priority"]) || isset($values["category_id"])) {
            $p = $this->tickets->findSlaPolicy(
                (int) $merged["category_id"],
                (string) $merged["priority"],
            );
            if (!$p) {
                throw new ValidationException([
                    [
                        "code" => "sla_policy_missing",
                        "detail" =>
                            "No active SLA policy matches the updated category and priority.",
                    ],
                ]);
            }
            $values["sla_policy_id"] = $p->id();
        }
        $ticket = $this->tickets->update($id, $values, $actorId);
        $this->tickets->history(
            $id,
            "updated",
            $actorId,
            "Ticket details updated.",
            metadata: ["changed_fields" => array_keys($values)],
        );
        $this->audit(
            $actorId,
            "support.ticket.updated",
            $id,
            $old->toArray(),
            $ticket->toArray(),
        );
        return $ticket;
    }
    public function delete(int $id, int $actorId): void
    {
        $old = $this->require($id);
        $this->tickets->softDelete($id, $actorId);
        $this->audit(
            $actorId,
            "support.ticket.deleted",
            $id,
            $old->toArray(),
            null,
        );
    }
    public function assign(
        int $id,
        AssignmentData $data,
        int $actorId,
    ): SupportTicket {
        $this->validator->assignment($data);
        return $this->tickets->transaction(function () use (
            $id,
            $data,
            $actorId,
        ) {
            $old = $this->require($id, true);
            $this->assignments->assign(
                $id,
                $data->teamId,
                $data->staffUserId,
                $actorId,
                $data->reason,
            );
            $newStatus = in_array($old->status(), ["new", "open"], true)
                ? "assigned"
                : $old->status();
            $ticket = $this->tickets->update(
                $id,
                ["status" => $newStatus],
                $actorId,
            );
            $this->tickets->history(
                $id,
                "assigned",
                $actorId,
                "Ticket assigned or reassigned.",
                $old->status(),
                $newStatus,
                [
                    "team_id" => $data->teamId,
                    "staff_user_id" => $data->staffUserId,
                    "reason" => $data->reason,
                ],
            );
            $this->audit(
                $actorId,
                "support.ticket.assigned",
                $id,
                $old->toArray(),
                $ticket->toArray(),
            );
            EventDispatcher::dispatch(
                "support.ticket.assigned",
                $ticket->toArray(),
            );
            return $ticket;
        });
    }
    public function transition(
        int $id,
        string $status,
        int $actorId,
        ?string $resolution = null,
        ?string $rootCause = null,
    ): SupportTicket {
        return $this->tickets->transaction(function () use (
            $id,
            $status,
            $actorId,
            $resolution,
            $rootCause,
        ) {
            $old = $this->require($id, true);
            $current = $old->toArray();
            $effectiveResolution =
                $resolution ?? ($current["resolution"] ?? null);
            $this->workflow->validate(
                $old->status(),
                $status,
                $effectiveResolution,
            );
            $updates = ["status" => $status];
            if ($resolution !== null) {
                $updates["resolution"] = $resolution;
            }
            if ($rootCause !== null) {
                $updates["root_cause"] = $rootCause;
            }
            $now = gmdate("Y-m-d H:i:s");
            if ($status === "waiting_customer" && (int) ($current["pause_while_waiting_customer"] ?? 0) === 1) {
                $updates["waiting_started_at"] = $now;
            } elseif ($current["waiting_started_at"] !== null) {
                $paused = max(
                    0,
                    time() - strtotime((string) $current["waiting_started_at"]),
                );
                $updates["waiting_started_at"] = null;
                $updates["sla_paused_seconds"] =
                    (int) $current["sla_paused_seconds"] + $paused;
                $updates["resolution_due_at"] = gmdate(
                    "Y-m-d H:i:s",
                    strtotime((string) $current["resolution_due_at"]) + $paused,
                );
            }
            if ($status === "resolved") {
                $updates["resolved_at"] = $now;
            }
            if ($status === "closed") {
                $updates["closed_at"] = $now;
                $updates["closed_by"] = $actorId;
            }
            if (
                in_array(
                    $old->status(),
                    ["resolved", "closed", "cancelled"],
                    true,
                ) &&
                in_array($status, ["open", "in_progress"], true)
            ) {
                $updates["resolved_at"] = null;
                $updates["closed_at"] = null;
                $updates["closed_by"] = null;
            }
            $ticket = $this->tickets->update($id, $updates, $actorId);
            $event = match ($status) {
                "resolved" => "resolved",
                "closed" => "closed",
                "escalated" => "escalated",
                default => "status_changed",
            };
            $this->tickets->history(
                $id,
                $event,
                $actorId,
                "Ticket status changed from " .
                    $old->status() .
                    " to " .
                    $status .
                    ".",
                $old->status(),
                $status,
            );
            $this->audit(
                $actorId,
                "support.ticket." . $event,
                $id,
                $old->toArray(),
                $ticket->toArray(),
            );
            EventDispatcher::dispatch(
                "support.ticket.updated",
                $ticket->toArray(),
            );
            return $ticket;
        });
    }
    public function escalate(
        int $id,
        EscalationData $data,
        int $actorId,
    ): SupportTicket {
        if ($data->reason === "") {
            throw new ValidationException([
                [
                    "code" => "reason_required",
                    "detail" => "An escalation reason is required.",
                ],
            ]);
        }
        $ticket = $this->transition($id, "escalated", $actorId);
        $a = $ticket->toArray();
        $ticket = $this->tickets->update(
            $id,
            [
                "escalation_level" => (int) $a["escalation_level"] + 1,
                "escalated_at" => gmdate("Y-m-d H:i:s"),
            ],
            $actorId,
        );
        if ($data->teamId) {
            $this->assignments->assign(
                $id,
                $data->teamId,
                null,
                $actorId,
                $data->reason,
            );
        }
        $this->tickets->history(
            $id,
            "escalated",
            $actorId,
            "Ticket escalated: " . $data->reason,
            metadata: ["team_id" => $data->teamId],
        );
        return $ticket;
    }
    public function merge(
        int $targetId,
        MergeTicketsData $data,
        int $actorId,
    ): SupportTicket {
        $this->validator->merge($data, $targetId);
        return $this->tickets->transaction(function () use (
            $targetId,
            $data,
            $actorId,
        ) {
            $target = $this->require($targetId, true);
            if (in_array($target->status(), ["closed", "cancelled"], true)) {
                throw new ValidationException([
                    [
                        "code" => "invalid_merge_target",
                        "detail" =>
                            "Closed or cancelled tickets cannot be merge targets.",
                    ],
                ]);
            }
            foreach ($data->sourceIds as $sourceId) {
                $source = $this->require($sourceId, true);
                if (!empty($source->toArray()["merged_into_ticket_id"])) {
                    throw new ValidationException([
                        [
                            "code" => "already_merged",
                            "detail" =>
                                "One of the source tickets is already merged.",
                        ],
                    ]);
                }
                $this->tickets->update(
                    $sourceId,
                    [
                        "status" => "cancelled",
                        "merged_into_ticket_id" => $targetId,
                    ],
                    $actorId,
                );
                $this->tickets->history(
                    $sourceId,
                    "merged",
                    $actorId,
                    "Ticket merged into " .
                        $target->toArray()["ticket_number"] .
                        ".",
                    $source->status(),
                    "cancelled",
                    [
                        "target_ticket_id" => $targetId,
                        "reason" => $data->reason,
                    ],
                );
            }
            $this->tickets->history(
                $targetId,
                "merge_received",
                $actorId,
                "Tickets merged into this ticket.",
                metadata: [
                    "source_ticket_ids" => $data->sourceIds,
                    "reason" => $data->reason,
                ],
            );
            $this->audit($actorId, "support.ticket.merged", $targetId, null, [
                "source_ticket_ids" => $data->sourceIds,
            ]);
            return $this->require($targetId);
        });
    }
    public function split(
        int $id,
        SplitTicketData $data,
        int $actorId,
    ): SupportTicket {
        $this->validator->split($data);
        $parent = $this->require($id);
        $p = $parent->toArray();
        $create = CreateTicketData::fromArray([
            ...$p,
            "subject" => $data->subject,
            "description" => $data->description,
            "category_id" => $data->categoryId ?? $p["category_id"],
            "priority" => $data->priority ?? $p["priority"],
            "source" => "staff",
        ]);
        $this->validator->create($create);
        $policy = $this->tickets->findSlaPolicy(
            $create->categoryId,
            $create->priority,
        );
        if (!$policy) {
            throw new ValidationException([
                [
                    "code" => "sla_policy_missing",
                    "detail" => "No SLA policy matches the split ticket.",
                ],
            ]);
        }
        return $this->tickets->transaction(function () use (
            $id,
            $create,
            $policy,
            $actorId,
            $data,
        ) {
            $child = $this->tickets->create(
                $create->toArray(),
                $actorId,
                $policy,
                $id,
            );
            $this->tickets->history(
                $id,
                "split",
                $actorId,
                "A child ticket was split from this ticket.",
                metadata: [
                    "child_ticket_id" => $child->id(),
                    "reason" => $data->reason,
                ],
            );
            $this->tickets->history(
                $child->id(),
                "split_from",
                $actorId,
                "Ticket split from parent ticket.",
                metadata: [
                    "parent_ticket_id" => $id,
                    "reason" => $data->reason,
                ],
            );
            $this->audit(
                $actorId,
                "support.ticket.split",
                $child->id(),
                null,
                $child->toArray(),
            );
            return $child;
        });
    }
    public function comment(
        int $id,
        CreateCommentData $data,
        int $actorId,
    ): TicketComment {
        $this->validator->comment($data);
        $ticket = $this->require($id);
        $customerId =
            $data->type === "customer_reply"
                ? $data->customerId ?? (int) $ticket->toArray()["customer_id"]
                : null;
        $comment = $this->comments->create(
            $id,
            $data->type,
            $data->body,
            $actorId,
            $customerId,
        );
        if (
            $data->type === "staff_reply" &&
            $ticket->toArray()["first_responded_at"] === null
        ) {
            $this->tickets->update(
                $id,
                ["first_responded_at" => gmdate("Y-m-d H:i:s")],
                $actorId,
            );
        }
        $this->tickets->history(
            $id,
            "comment_added",
            $actorId,
            match ($data->type) {
                "internal_note" => "Internal note added.",
                "customer_reply" => "Customer reply recorded.",
                default => "Staff reply added.",
            },
            metadata: [
                "comment_id" => $comment->id(),
                "comment_type" => $data->type,
            ],
        );
        EventDispatcher::dispatch("support.ticket.replied", [
            "ticket_id" => $id,
            "comment" => $comment->toArray(),
        ]);
        return $comment;
    }
    private function require(int $id, bool $lock = false): SupportTicket
    {
        return $this->tickets->find($id, $lock) ??
            throw new NotFoundException("Support ticket not found.");
    }
    private function audit(
        int $actor,
        string $action,
        int $id,
        ?array $old,
        ?array $new,
    ): void {
        $this->audit->log($actor, $action, "support_ticket", $id, $old, $new);
    }
}
