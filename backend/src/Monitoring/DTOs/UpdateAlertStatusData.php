<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

final class UpdateAlertStatusData
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $notes = null,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            status: (string) ($payload['status'] ?? ''),
            notes: isset($payload['notes']) ? (string) $payload['notes'] : null,
        );
    }
}
