<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class AssignmentData
{
    public function __construct(
        public readonly ?int $teamId,
        public readonly ?int $staffUserId,
        public readonly ?string $reason,
    ) {}
    /** @param array<string,mixed> $d */ public static function fromArray(
        array $d,
    ): self {
        return new self(
            isset($d["team_id"]) ? (int) $d["team_id"] : null,
            isset($d["staff_user_id"]) ? (int) $d["staff_user_id"] : null,
            isset($d["reason"]) ? trim((string) $d["reason"]) : null,
        );
    }
}
