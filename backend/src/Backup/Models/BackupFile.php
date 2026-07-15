<?php

declare(strict_types=1);

namespace SkyFi\Backup\Models;

final class BackupFile
{
    public function __construct(
        public readonly int $id,
        public readonly int $jobId,
        public readonly int $storageProviderId,
        public readonly string $filePath,
        public readonly int $fileSize,
        public readonly string $checksum,
        public readonly ?array $metadata,
        public readonly ?string $verifiedAt,
        public readonly ?string $expiresAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $storageProviderName = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->jobId,
            'storage_provider_id' => $this->storageProviderId,
            'file_path' => $this->filePath,
            'file_size' => $this->fileSize,
            'checksum' => $this->checksum,
            'metadata' => $this->metadata,
            'verified_at' => $this->verifiedAt,
            'expires_at' => $this->expiresAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'storage_provider_name' => $this->storageProviderName,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            jobId: (int) $row['job_id'],
            storageProviderId: (int) $row['storage_provider_id'],
            filePath: (string) $row['file_path'],
            fileSize: (int) $row['file_size'],
            checksum: (string) $row['checksum'],
            metadata: isset($row['metadata']) ? json_decode($row['metadata'], true) : null,
            verifiedAt: $row['verified_at'] ?? null,
            expiresAt: $row['expires_at'] ?? null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            storageProviderName: $row['storage_provider_name'] ?? null,
        );
    }
}
