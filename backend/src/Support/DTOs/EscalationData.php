<?php

declare(strict_types=1);
namespace SkyFi\Support\DTOs;
final class EscalationData
{
    public function __construct(
        public readonly string $reason,
        public readonly ?int $teamId,
    ) {}
    /** @param array<string,mixed> $d */ public static function fromArray(
        array $d,
    ): self {
        return new self(
            trim((string) ($d["reason"] ?? "")),
            isset($d["team_id"]) ? (int) $d["team_id"] : null,
        );
    }
}
