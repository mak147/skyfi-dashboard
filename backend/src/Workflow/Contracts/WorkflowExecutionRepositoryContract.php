<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

use SkyFi\Workflow\DomainModels\WorkflowExecution;
use SkyFi\Workflow\DTOs\ExecutionListFilters;

interface WorkflowExecutionRepositoryContract
{
    /** @return array{items: list<WorkflowExecution>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(ExecutionListFilters $filters): array;

    public function find(int $id): ?WorkflowExecution;

    public function findByUuid(string $uuid): ?WorkflowExecution;

    /** @param array<string, mixed> $data */
    public function create(array $data): WorkflowExecution;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?WorkflowExecution;

    /** @return list<WorkflowExecution> */
    public function findDueScheduled(int $limit = 50): array;

    /** @return list<WorkflowExecution> */
    public function findDueRetries(int $limit = 50): array;

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 10): array;
}
