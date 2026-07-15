<?php

declare(strict_types=1);

namespace SkyFi\Audit\Contracts;

use SkyFi\Audit\DomainModels\AuditLog;
use SkyFi\Audit\DTOs\AuditLogFilters;

interface AuditLogRepositoryContract
{
    /** @return array{items: list<AuditLog>, page: int, perPage: int, total: int, lastPage: int} */
    public function search(AuditLogFilters $filters): array;

    public function find(int $id): ?AuditLog;

    /** @param array<string, mixed> $data */
    public function create(array $data): AuditLog;

    /** @return array<string, mixed> */
    public function getDashboardStats(): array;

    /** @return list<string> */
    public function getDistinctModules(): array;

    /** @return list<string> */
    public function getDistinctActions(): array;

    /** @return list<string> */
    public function getDistinctEntityTypes(): array;
}
