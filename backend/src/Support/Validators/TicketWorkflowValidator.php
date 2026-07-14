<?php

declare(strict_types=1);
namespace SkyFi\Support\Validators;
use SkyFi\Shared\Exceptions\ValidationException;
final class TicketWorkflowValidator
{
    private const TRANSITIONS = [
        "new" => ["open", "assigned", "escalated", "cancelled"],
        "open" => [
            "assigned",
            "in_progress",
            "waiting_customer",
            "escalated",
            "resolved",
            "cancelled",
        ],
        "assigned" => [
            "open",
            "in_progress",
            "waiting_customer",
            "escalated",
            "resolved",
            "cancelled",
        ],
        "in_progress" => [
            "waiting_customer",
            "escalated",
            "resolved",
            "cancelled",
        ],
        "waiting_customer" => [
            "open",
            "in_progress",
            "escalated",
            "resolved",
            "cancelled",
        ],
        "escalated" => [
            "assigned",
            "in_progress",
            "waiting_customer",
            "resolved",
            "cancelled",
        ],
        "resolved" => ["closed", "open", "in_progress"],
        "closed" => ["open", "in_progress"],
        "cancelled" => ["open"],
    ];
    public function validate(
        string $from,
        string $to,
        ?string $resolution,
    ): void {
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new ValidationException([
                [
                    "code" => "invalid_transition",
                    "detail" => "Ticket cannot move from {$from} to {$to}.",
                ],
            ]);
        }
        if (
            in_array($to, ["resolved", "closed"], true) &&
            trim((string) $resolution) === ""
        ) {
            throw new ValidationException([
                [
                    "code" => "resolution_required",
                    "detail" =>
                        "Resolution is required before resolving or closing a ticket.",
                    "source" => ["pointer" => "/data/attributes/resolution"],
                ],
            ]);
        }
    }
}
