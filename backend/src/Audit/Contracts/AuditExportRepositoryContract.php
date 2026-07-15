<?php

declare(strict_types=1);

namespace SkyFi\Audit\Contracts;

use SkyFi\Audit\DomainModels\AuditExport;
use SkyFi\Audit\DTOs\ExportRequestData;

interface AuditExportRepositoryContract
{
    /** @return list<AuditExport> */
    public function findByUser(int $userId): array;

    public function find(int $id): ?AuditExport;

    /** @param array<string, mixed> $filters */
    public function create(int $userId, string $format, array $filters): AuditExport;

    public function updateStatus(int $id, string $status, ?string $filePath = null, ?string $errorMessage = null, int $rowCount = 0): ?AuditExport;
}
