<?php

declare(strict_types=1);

namespace SkyFi\Backup\Models;

final class BackupJob
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $scheduleId,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $startedAt,
        public readonly ?string $finishedAt,
        public readonly ?string $errorMessage,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $scheduleName = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->scheduleId,
            'type' => $this->type,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'schedule_name' => $this->scheduleName,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            scheduleId: isset($row['schedule_id']) ? (int) $row['schedule_id'] : null,
            type: (string) $row['type'],
            status: (string) $row['status'],
            startedAt: $row['started_at'] ?? null,
            finishedAt: $row['finished_at'] ?? null,
            errorMessage: $row['error_message'] ?? null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            scheduleName: $row['schedule_name'] ?? null,
        );
    }
}
