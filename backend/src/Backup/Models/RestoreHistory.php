<?php

declare(strict_types=1);

namespace SkyFi\Backup\Models;

final class RestoreHistory
{
    public function __construct(
        public readonly int $id,
        public readonly int $backupFileId,
        public readonly string $status,
        public readonly string $targetEnvironment,
        public readonly ?string $startedAt,
        public readonly ?string $finishedAt,
        public readonly ?string $errorMessage,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $backupFileName = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'backup_file_id' => $this->backupFileId,
            'status' => $this->status,
            'target_environment' => $this->targetEnvironment,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'backup_file_name' => $this->backupFileName,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            backupFileId: (int) $row['backup_file_id'],
            status: (string) $row['status'],
            targetEnvironment: (string) $row['target_environment'],
            startedAt: $row['started_at'] ?? null,
            finishedAt: $row['finished_at'] ?? null,
            errorMessage: $row['error_message'] ?? null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            backupFileName: $row['file_path'] ?? null,
        );
    }
}
