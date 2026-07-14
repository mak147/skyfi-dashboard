<?php

declare(strict_types=1);
namespace SkyFi\Support\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Support\DTOs\{
    AssignmentData,
    CreateCommentData,
    CreateTicketData,
    MergeTicketsData,
    SplitTicketData,
};
final class TicketValidator
{
    public const STATUSES = [
        "new",
        "open",
        "assigned",
        "in_progress",
        "waiting_customer",
        "escalated",
        "resolved",
        "closed",
        "cancelled",
    ];
    private const PRIORITIES = ["low", "normal", "high", "urgent"];
    private const SOURCES = [
        "portal",
        "email",
        "phone",
        "staff",
        "system",
        "monitoring",
    ];
    public function create(CreateTicketData $d): void
    {
        $e = [];
        if ($d->customerId < 1) {
            $e[] = $this->err("customer_id", "A customer is required.");
        }
        if ($d->categoryId < 1) {
            $e[] = $this->err("category_id", "A category is required.");
        }
        if ($d->subject === "" || mb_strlen($d->subject) > 255) {
            $e[] = $this->err(
                "subject",
                "Subject is required and must not exceed 255 characters.",
            );
        }
        if ($d->description === "") {
            $e[] = $this->err("description", "Description is required.");
        }
        if (!in_array($d->priority, self::PRIORITIES, true)) {
            $e[] = $this->err("priority", "Priority is invalid.");
        }
        if (!in_array($d->source, self::SOURCES, true)) {
            $e[] = $this->err("source", "Source is invalid.");
        }
        $this->throw($e);
    }
    public function assignment(AssignmentData $d): void
    {
        if ($d->teamId === null && $d->staffUserId === null) {
            $this->throw([
                $this->err("assignment", "Select a team or staff member."),
            ]);
        }
    }
    public function comment(CreateCommentData $d): void
    {
        $e = [];
        if (
            !in_array(
                $d->type,
                ["internal_note", "customer_reply", "staff_reply"],
                true,
            )
        ) {
            $e[] = $this->err("type", "Comment type is invalid.");
        }
        if ($d->body === "" || mb_strlen($d->body) > 20000) {
            $e[] = $this->err(
                "body",
                "Comment is required and must not exceed 20,000 characters.",
            );
        }
        $this->throw($e);
    }
    public function merge(MergeTicketsData $d, int $target): void
    {
        $e = [];
        if ($d->sourceIds === []) {
            $e[] = $this->err(
                "source_ticket_ids",
                "Select at least one source ticket.",
            );
        }
        if (in_array($target, $d->sourceIds, true)) {
            $e[] = $this->err(
                "source_ticket_ids",
                "A ticket cannot be merged into itself.",
            );
        }
        if ($d->reason === "") {
            $e[] = $this->err("reason", "A merge reason is required.");
        }
        $this->throw($e);
    }
    public function split(SplitTicketData $d): void
    {
        $e = [];
        if ($d->subject === "") {
            $e[] = $this->err("subject", "Subject is required.");
        }
        if ($d->description === "") {
            $e[] = $this->err("description", "Description is required.");
        }
        if ($d->reason === "") {
            $e[] = $this->err("reason", "A split reason is required.");
        }
        $this->throw($e);
    }
    /** @return array<string,mixed> */ private function err(
        string $field,
        string $detail,
    ): array {
        return [
            "code" => "validation_error",
            "detail" => $detail,
            "source" => ["pointer" => "/data/attributes/" . $field],
        ];
    }
    /** @param array<int,array<string,mixed>> $errors */ private function throw(
        array $errors,
    ): void {
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
