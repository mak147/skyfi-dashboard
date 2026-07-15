<?php

declare(strict_types=1);

namespace SkyFi\Audit\Contracts;

use SkyFi\Audit\DTOs\AuditLogFilters;
use SkyFi\Audit\DTOs\ActivityFilters;

interface AuditServiceContract
{
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $module = null,
        ?string $resource = null,
        string $severity = 'info',
        ?string $correlationId = null,
        ?string $url = null,
        ?array $complianceTags = null,
    ): void;

    public function logActivity(
        ?int $userId,
        string $module,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?string $description = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null,
        ?string $correlationId = null,
    ): void;

    /** @return array{items: list<array<string, mixed>>, page: int, perPage: int, total: int, lastPage: int} */
    public function searchAuditLogs(AuditLogFilters $filters): array;

    /** @return array<string, mixed> */
    public function getAuditLog(int $id): array;

    /** @return array<string, mixed> */
    public function getDashboardStats(): array;

    /** @return array<string, mixed> */
    public function getFilterOptions(): array;

    /** @return array{items: list<array<string, mixed>>, page: int, perPage: int, total: int, lastPage: int} */
    public function searchActivity(ActivityFilters $filters): array;

    /** @return array{items: list<array<string, mixed>>, page: int, perPage: int, total: int, lastPage: int} */
    public function getResourceHistory(string $entityType, int $entityId, int $page = 1, int $perPage = 25): array;

    /** @return array<string, mixed> */
    public function requestExport(int $userId, ExportRequestData $data): array;

    /** @return list<array<string, mixed>> */
    public function getExportHistory(int $userId): array;

    /** @return array<string, mixed> */
    public function getExport(int $id): array;
}
