<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

use SkyFi\Workflow\DomainModels\Workflow;
use SkyFi\Workflow\DTOs\WorkflowListFilters;

interface WorkflowRepositoryContract
{
    /** @return array{items: list<Workflow>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(WorkflowListFilters $filters): array;

    public function find(int $id): ?Workflow;

    public function findByUuid(string $uuid): ?Workflow;

    /** @return list<Workflow> */
    public function findEnabledByEvent(string $eventKey): array;

    /** @param array<string, mixed> $data */
    public function create(array $data): Workflow;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?Workflow;

    public function softDelete(int $id): bool;

    public function recordExecutionStats(int $id, bool $success): void;

    /** @return array<string, mixed> */
    public function dashboardStats(): array;
}
