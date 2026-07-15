<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Contracts;

use SkyFi\Workflow\DomainModels\Workflow;
use SkyFi\Workflow\DomainModels\WorkflowExecution;
use SkyFi\Workflow\DomainModels\WorkflowVersion;

interface WorkflowEngineContract
{
    /**
     * @param array<string, mixed> $payload
     */
    public function enqueue(
        Workflow $workflow,
        WorkflowVersion $version,
        array $payload,
        string $source,
        ?string $eventKey = null,
        ?int $actorUserId = null,
        bool $dryRun = false,
    ): WorkflowExecution;

    public function execute(WorkflowExecution $execution, bool $dryRun = false): WorkflowExecution;
}
