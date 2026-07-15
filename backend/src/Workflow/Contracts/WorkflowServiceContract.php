<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

use SkyFi\Workflow\DomainModels\Workflow;
use SkyFi\Workflow\DomainModels\WorkflowExecution;
use SkyFi\Workflow\DomainModels\WorkflowVersion;
use SkyFi\Workflow\DTOs\CreateWorkflowData;
use SkyFi\Workflow\DTOs\ExecutionListFilters;
use SkyFi\Workflow\DTOs\RunWorkflowData;
use SkyFi\Workflow\DTOs\UpdateWorkflowData;
use SkyFi\Workflow\DTOs\WorkflowListFilters;

interface WorkflowServiceContract
{
    /** @return array{items: list<Workflow>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(WorkflowListFilters $filters): array;

    /** @return array<string, mixed> */
    public function get(int $id): array;

    public function create(int $userId, CreateWorkflowData $data): Workflow;

    public function update(int $id, int $userId, UpdateWorkflowData $data): Workflow;

    public function delete(int $id): void;

    public function enable(int $id, int $userId): Workflow;

    public function disable(int $id, int $userId): Workflow;

    public function pause(int $id, int $userId): Workflow;

    public function resume(int $id, int $userId): Workflow;

    public function cloneWorkflow(int $id, int $userId): Workflow;

    /** @return list<WorkflowVersion> */
    public function versions(int $id): array;

    public function version(int $id, int $versionId): WorkflowVersion;

    public function run(int $id, int $userId, RunWorkflowData $data): WorkflowExecution;

    public function test(int $id, int $userId, RunWorkflowData $data): WorkflowExecution;

    /** @return array{items: list<\SkyFi\Workflow\DomainModels\WorkflowExecution>, page: int, perPage: int, total: int, lastPage: int} */
    public function executions(ExecutionListFilters $filters): array;

    public function execution(int $executionId): WorkflowExecution;

    public function retryExecution(int $executionId, int $userId): WorkflowExecution;

    public function cancelExecution(int $executionId, int $userId): WorkflowExecution;

    public function pauseExecution(int $executionId, int $userId): WorkflowExecution;

    public function resumeExecution(int $executionId, int $userId): WorkflowExecution;

    /** @return array<string, mixed> */
    public function dashboard(): array;

    /** @return array<string, mixed> */
    public function catalogs(): array;

    public function processScheduler(): int;
}
