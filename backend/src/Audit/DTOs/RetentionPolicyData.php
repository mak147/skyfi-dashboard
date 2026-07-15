<?php

declare(strict_types=1);

namespace SkyFi\Audit\DTOs;

final class RetentionPolicyData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly string $module = '*',
        public readonly string $actionPattern = '*',
        public readonly int $retentionDays = 365,
        public readonly int $autoArchive = 0,
        public readonly ?string $archiveLocation = null,
        public readonly int $isActive = 1,
        public readonly ?int $createdBy = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            module: (string) ($data['module'] ?? '*'),
            actionPattern: (string) ($data['action_pattern'] ?? '*'),
            retentionDays: (int) ($data['retention_days'] ?? 365),
            autoArchive: isset($data['auto_archive']) ? (int) $data['auto_archive'] : 0,
            archiveLocation: isset($data['archive_location']) ? (string) $data['archive_location'] : null,
            isActive: isset($data['is_active']) ? (int) $data['is_active'] : 1,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
        );
    }
}
