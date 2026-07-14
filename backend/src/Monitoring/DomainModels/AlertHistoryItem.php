<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DomainModels;

final class AlertHistoryItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $alertId,
        public readonly ?string $oldStatus,
        public readonly string $newStatus,
        public readonly ?int $changedBy,
        public readonly ?string $notes,
        public readonly string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'alert_id' => $this->alertId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            alertId: (int) $row['alert_id'],
            oldStatus: isset($row['old_status']) ? (string) $row['old_status'] : null,
            newStatus: (string) $row['new_status'],
            changedBy: isset($row['changed_by']) && $row['changed_by'] !== null ? (int) $row['changed_by'] : null,
            notes: isset($row['notes']) ? (string) $row['notes'] : null,
            createdAt: (string) $row['created_at'],
        );
    }
}
