<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DomainModels;

final class SyncEventLog
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $routerId,
        public readonly string $syncType,
        public readonly string $status,
        public readonly int $itemsSynced,
        public readonly ?string $errorMessage,
        public readonly float $executionTimeMs,
        public readonly string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'router_id' => $this->routerId,
            'sync_type' => $this->syncType,
            'status' => $this->status,
            'items_synced' => $this->itemsSynced,
            'error_message' => $this->errorMessage,
            'execution_time_ms' => $this->executionTimeMs,
            'created_at' => $this->createdAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            routerId: isset($row['router_id']) && $row['router_id'] !== null ? (int) $row['router_id'] : null,
            syncType: (string) $row['sync_type'],
            status: (string) $row['status'],
            itemsSynced: (int) ($row['items_synced'] ?? 0),
            errorMessage: isset($row['error_message']) ? (string) $row['error_message'] : null,
            executionTimeMs: (float) ($row['execution_time_ms'] ?? 0.0),
            createdAt: (string) $row['created_at'],
        );
    }
}
