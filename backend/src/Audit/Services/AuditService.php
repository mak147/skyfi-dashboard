<?php

declare(strict_types=1);

namespace SkyFi\Audit\Services;

use SkyFi\Audit\Contracts\ActivityRepositoryContract;
use SkyFi\Audit\Contracts\AuditExportRepositoryContract;
use SkyFi\Audit\Contracts\AuditLogRepositoryContract;
use SkyFi\Audit\Contracts\AuditServiceContract;
use SkyFi\Audit\DTOs\ActivityFilters;
use SkyFi\Audit\DTOs\AuditLogFilters;
use SkyFi\Audit\DTOs\ExportRequestData;
use SkyFi\Shared\Exceptions\NotFoundException;

final class AuditService implements AuditServiceContract
{
    public function __construct(
        private readonly AuditLogRepositoryContract $auditLogs,
        private readonly ActivityRepositoryContract $activities,
        private readonly AuditExportRepositoryContract $exports,
        private readonly AuditExportService $exportService,
    ) {}

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
    ): void {
        $this->auditLogs->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'module' => $module,
            'resource' => $resource,
            'severity' => $severity,
            'correlation_id' => $correlationId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'url' => $url,
            'compliance_tags' => $complianceTags,
            'is_immutable' => 1,
        ]);

        // Also record a lightweight activity event
        $this->activities->create([
            'user_id' => $userId,
            'module' => $module ?? $entityType,
            'action' => $action,
            'resource_type' => $entityType,
            'resource_id' => $entityId,
            'description' => "{$action} on {$entityType}" . ($entityId ? " #{$entityId}" : ''),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => [
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ],
            'correlation_id' => $correlationId,
        ]);
    }

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
    ): void {
        $this->activities->create([
            'user_id' => $userId,
            'module' => $module,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'description' => $description ?? "{$action} on {$resourceType}" . ($resourceId ? " #{$resourceId}" : ''),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $metadata,
            'correlation_id' => $correlationId,
        ]);
    }

    public function searchAuditLogs(AuditLogFilters $filters): array
    {
        $result = $this->auditLogs->search($filters);
        return [
            'items' => array_map(static fn($l) => $l->toArray(), $result['items']),
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'total' => $result['total'],
            'lastPage' => $result['lastPage'],
        ];
    }

    public function getAuditLog(int $id): array
    {
        $log = $this->auditLogs->find($id);
        if ($log === null) {
            throw new NotFoundException('Audit log not found.');
        }
        return $log->toArray();
    }

    public function getDashboardStats(): array
    {
        return $this->auditLogs->getDashboardStats();
    }

    public function getFilterOptions(): array
    {
        return [
            'modules' => $this->auditLogs->getDistinctModules(),
            'actions' => $this->auditLogs->getDistinctActions(),
            'entity_types' => $this->auditLogs->getDistinctEntityTypes(),
            'severities' => ['info', 'warning', 'critical'],
        ];
    }

    public function searchActivity(ActivityFilters $filters): array
    {
        $result = $this->activities->search($filters);
        return [
            'items' => array_map(static fn($e) => $e->toArray(), $result['items']),
            'page' => $result['page'],
            'perPage' => $result['perPage'],
            'total' => $result['total'],
            'lastPage' => $result['lastPage'],
        ];
    }

    public function getResourceHistory(string $entityType, int $entityId, int $page = 1, int $perPage = 25): array
    {
        $filters = new AuditLogFilters(
            entityType: $entityType,
            entityId: $entityId,
            page: $page,
            perPage: $perPage,
        );
        return $this->searchAuditLogs($filters);
    }

    public function requestExport(int $userId, ExportRequestData $data): array
    {
        return $this->exportService->createExport($userId, $data);
    }

    public function getExportHistory(int $userId): array
    {
        return array_map(static fn($e) => $e->toArray(), $this->exports->findByUser($userId));
    }

    public function getExport(int $id): array
    {
        $export = $this->exports->find($id);
        if ($export === null) {
            throw new NotFoundException('Export not found.');
        }
        return $export->toArray();
    }
}
