<?php

declare(strict_types=1);

namespace SkyFi\Backup\Models;

final class BackupSchedule
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $cronExpression,
        public readonly int $retentionDays,
        public readonly int $storageProviderId,
        public readonly bool $isActive,
        public readonly ?string $lastRunAt,
        public readonly ?string $nextRunAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $storageProviderName = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'cron_expression' => $this->cronExpression,
            'retention_days' => $this->retentionDays,
            'storage_provider_id' => $this->storageProviderId,
            'is_active' => $this->isActive,
            'last_run_at' => $this->lastRunAt,
            'next_run_at' => $this->nextRunAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'storage_provider_name' => $this->storageProviderName,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            type: (string) $row['type'],
            cronExpression: (string) $row['cron_expression'],
            retentionDays: (int) $row['retention_days'],
            storageProviderId: (int) $row['storage_provider_id'],
            isActive: (bool) $row['is_active'],
            lastRunAt: $row['last_run_at'] ?? null,
            nextRunAt: $row['next_run_at'] ?? null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            storageProviderName: $row['storage_provider_name'] ?? null,
        );
    }
}
