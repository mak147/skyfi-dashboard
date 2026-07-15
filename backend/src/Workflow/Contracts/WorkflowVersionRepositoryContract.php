<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

use SkyFi\Workflow\DomainModels\WorkflowVersion;

interface WorkflowVersionRepositoryContract
{
    public function find(int $id): ?WorkflowVersion;

    /** @return list<WorkflowVersion> */
    public function listForWorkflow(int $workflowId): array;

    public function latestVersionNumber(int $workflowId): int;

    /**
     * @param array<string, mixed> $definition
     * @param list<array<string, mixed>> $triggers
     * @param list<array<string, mixed>> $conditions
     * @param list<array<string, mixed>> $actions
     */
    public function createVersion(
        int $workflowId,
        int $versionNumber,
        array $definition,
        array $triggers,
        array $conditions,
        array $actions,
        ?string $changelog,
        ?int $createdBy,
        bool $isPublished = true,
    ): WorkflowVersion;

    /** @return list<array<string, mixed>> */
    public function triggersForVersion(int $versionId): array;

    /** @return list<array<string, mixed>> */
    public function conditionsForVersion(int $versionId): array;

    /** @return list<array<string, mixed>> */
    public function actionsForVersion(int $versionId): array;
}
