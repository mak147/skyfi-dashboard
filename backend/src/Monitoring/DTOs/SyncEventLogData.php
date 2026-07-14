<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

final class SyncEventLogData
{
    public function __construct(
        public readonly ?int $routerId,
        public readonly string $syncType,
        public readonly string $status,
        public readonly int $itemsSynced,
        public readonly ?string $errorMessage,
        public readonly float $executionTimeMs,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            routerId: isset($payload['router_id']) && $payload['router_id'] !== null ? (int) $payload['router_id'] : null,
            syncType: (string) ($payload['sync_type'] ?? 'health_check'),
            status: (string) ($payload['status'] ?? 'success'),
            itemsSynced: (int) ($payload['items_synced'] ?? 0),
            errorMessage: isset($payload['error_message']) ? (string) $payload['error_message'] : null,
            executionTimeMs: (float) ($payload['execution_time_ms'] ?? 0.0),
        );
    }
}
