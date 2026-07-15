<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Services;

use PDO;
use SkyFi\Integration\Contracts\EventRegistryRepositoryContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Workflow\Contracts\WorkflowEngineContract;
use SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract;
use SkyFi\Workflow\Contracts\WorkflowRepositoryContract;
use SkyFi\Workflow\Contracts\WorkflowServiceContract;
use SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract;
use SkyFi\Workflow\DomainModels\Workflow;
use SkyFi\Workflow\DomainModels\WorkflowExecution;
use SkyFi\Workflow\DomainModels\WorkflowVersion;
use SkyFi\Workflow\DTOs\CreateWorkflowData;
use SkyFi\Workflow\DTOs\ExecutionListFilters;
use SkyFi\Workflow\DTOs\RunWorkflowData;
use SkyFi\Workflow\DTOs\UpdateWorkflowData;
use SkyFi\Workflow\DTOs\WorkflowListFilters;
use SkyFi\Workflow\Validators\WorkflowValidator;

final class WorkflowService implements WorkflowServiceContract
{
    public function __construct(
        private readonly WorkflowRepositoryContract $workflows,
        private readonly WorkflowVersionRepositoryContract $versions,
        private readonly WorkflowExecutionRepositoryContract $executions,
        private readonly WorkflowEngineContract $engine,
        private readonly WorkflowScheduler $scheduler,
        private readonly WorkflowCatalog $catalog,
        private readonly WorkflowValidator $validator,
        private readonly EventRegistryRepositoryContract $eventRegistry,
        private readonly PDO $pdo,
    ) {}

    public function list(WorkflowListFilters $filters): array
    {
        return $this->workflows->list($filters);
    }

    public function get(int $id): array
    {
        $workflow = $this->requireWorkflow($id);
        $activeVersion = $workflow->activeVersionId()
            ? $this->versions->find($workflow->activeVersionId())
            : null;
        $definition = $activeVersion?->definition() ?? [];
        $versionId = $activeVersion?->id();

        return [
            'workflow' => $workflow->toArray(),
            'active_version' => $activeVersion?->toArray(),
            'definition' => $definition,
            'triggers' => $versionId ? $this->versions->triggersForVersion($versionId) : [],
            'conditions' => $versionId ? $this->versions->conditionsForVersion($versionId) : [],
            'actions' => $versionId ? $this->versions->actionsForVersion($versionId) : [],
            'versions' => array_map(
                static fn (WorkflowVersion $v): array => $v->toArray(),
                $this->versions->listForWorkflow($id),
            ),
        ];
    }

    public function create(int $userId, CreateWorkflowData $data): Workflow
    {
        $this->validator->create($data);
        $definition = $this->normalizeDefinition($data->definition, $data->scheduleMode, $data->cronExpression, $data->delaySeconds);
        $eventKey = (string) ($definition['trigger']['event_key'] ?? '');

        $this->pdo->beginTransaction();
        try {
            $workflow = $this->workflows->create([
                'uuid' => $this->uuid(),
                'name' => $data->name,
                'description' => $data->description,
                'status' => $data->status,
                'is_enabled' => $data->isEnabled && $data->status === 'active',
                'trigger_event_key' => $eventKey,
                'schedule_mode' => $data->scheduleMode,
                'cron_expression' => $data->cronExpression ?? ($definition['schedule']['cron'] ?? null),
                'delay_seconds' => $data->delaySeconds,
                'max_retries' => $data->maxRetries,
                'retry_delay_seconds' => $data->retryDelaySeconds,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $version = $this->createVersionFromDefinition(
                $workflow->id(),
                1,
                $definition,
                $data->changelog ?? 'Initial version',
                $userId,
            );

            $workflow = $this->workflows->update($workflow->id(), [
                'active_version_id' => $version->id(),
                'updated_by' => $userId,
            ]) ?? $workflow;

            $this->pdo->commit();

            return $workflow;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update(int $id, int $userId, UpdateWorkflowData $data): Workflow
    {
        $this->validator->update($data);
        $workflow = $this->requireWorkflow($id);

        $this->pdo->beginTransaction();
        try {
            $update = ['updated_by' => $userId];
            if ($data->name !== null) {
                $update['name'] = $data->name;
            }
            if ($data->description !== null) {
                $update['description'] = $data->description;
            }
            if ($data->status !== null) {
                $update['status'] = $data->status;
            }
            if ($data->isEnabled !== null) {
                $update['is_enabled'] = $data->isEnabled;
            }
            if ($data->scheduleMode !== null) {
                $update['schedule_mode'] = $data->scheduleMode;
            }
            if ($data->cronExpression !== null) {
                $update['cron_expression'] = $data->cronExpression;
            }
            if ($data->delaySeconds !== null) {
                $update['delay_seconds'] = $data->delaySeconds;
            }
            if ($data->maxRetries !== null) {
                $update['max_retries'] = $data->maxRetries;
            }
            if ($data->retryDelaySeconds !== null) {
                $update['retry_delay_seconds'] = $data->retryDelaySeconds;
            }

            if ($data->definition !== null) {
                $scheduleMode = $data->scheduleMode ?? $workflow->scheduleMode();
                $definition = $this->normalizeDefinition(
                    $data->definition,
                    $scheduleMode,
                    $data->cronExpression ?? $workflow->cronExpression(),
                    $data->delaySeconds ?? $workflow->delaySeconds(),
                );
                $next = $this->versions->latestVersionNumber($id) + 1;
                $version = $this->createVersionFromDefinition(
                    $id,
                    $next,
                    $definition,
                    $data->changelog ?? "Version {$next}",
                    $userId,
                );
                $update['active_version_id'] = $version->id();
                $update['trigger_event_key'] = (string) ($definition['trigger']['event_key'] ?? $workflow->triggerEventKey());
            }

            $updated = $this->workflows->update($id, $update)
                ?? throw new NotFoundException('Workflow not found after update.');
            $this->pdo->commit();

            return $updated;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $this->requireWorkflow($id);
        if (!$this->workflows->softDelete($id)) {
            throw new NotFoundException('Workflow not found.');
        }
    }

    public function enable(int $id, int $userId): Workflow
    {
        $workflow = $this->requireWorkflow($id);
        if ($workflow->activeVersionId() === null) {
            throw new ValidationException([[
                'code' => 'no_version',
                'detail' => 'Workflow must have an active version before enabling.',
            ]]);
        }

        return $this->workflows->update($id, [
            'is_enabled' => true,
            'status' => 'active',
            'updated_by' => $userId,
        ]) ?? $workflow;
    }

    public function disable(int $id, int $userId): Workflow
    {
        $workflow = $this->requireWorkflow($id);

        return $this->workflows->update($id, [
            'is_enabled' => false,
            'status' => 'disabled',
            'updated_by' => $userId,
        ]) ?? $workflow;
    }

    public function pause(int $id, int $userId): Workflow
    {
        $workflow = $this->requireWorkflow($id);

        return $this->workflows->update($id, [
            'is_enabled' => false,
            'status' => 'paused',
            'updated_by' => $userId,
        ]) ?? $workflow;
    }

    public function resume(int $id, int $userId): Workflow
    {
        $workflow = $this->requireWorkflow($id);

        return $this->workflows->update($id, [
            'is_enabled' => true,
            'status' => 'active',
            'updated_by' => $userId,
        ]) ?? $workflow;
    }

    public function cloneWorkflow(int $id, int $userId): Workflow
    {
        $detail = $this->get($id);
        $source = $this->requireWorkflow($id);
        $definition = is_array($detail['definition'] ?? null) ? $detail['definition'] : [];

        return $this->create($userId, new CreateWorkflowData(
            name: $source->toArray()['name'] . ' (Copy)',
            description: $source->toArray()['description'] ?? null,
            status: 'draft',
            isEnabled: false,
            scheduleMode: $source->scheduleMode(),
            cronExpression: $source->cronExpression(),
            delaySeconds: $source->delaySeconds(),
            maxRetries: $source->maxRetries(),
            retryDelaySeconds: $source->retryDelaySeconds(),
            definition: $definition,
            changelog: 'Cloned from workflow #' . $id,
        ));
    }

    public function versions(int $id): array
    {
        $this->requireWorkflow($id);

        return $this->versions->listForWorkflow($id);
    }

    public function version(int $id, int $versionId): WorkflowVersion
    {
        $this->requireWorkflow($id);
        $version = $this->versions->find($versionId);
        if ($version === null || $version->workflowId() !== $id) {
            throw new NotFoundException('Workflow version not found.');
        }

        return $version;
    }

    public function run(int $id, int $userId, RunWorkflowData $data): WorkflowExecution
    {
        $workflow = $this->requireWorkflow($id);
        $version = $this->resolveVersion($workflow, $data->versionId);

        return $this->engine->enqueue(
            $workflow,
            $version,
            $data->payload,
            $data->dryRun ? 'test' : 'manual',
            $workflow->triggerEventKey(),
            $userId,
            $data->dryRun,
        );
    }

    public function test(int $id, int $userId, RunWorkflowData $data): WorkflowExecution
    {
        return $this->run($id, $userId, new RunWorkflowData(
            payload: $data->payload,
            dryRun: true,
            versionId: $data->versionId,
        ));
    }

    public function executions(ExecutionListFilters $filters): array
    {
        return $this->executions->list($filters);
    }

    public function execution(int $executionId): WorkflowExecution
    {
        return $this->executions->find($executionId)
            ?? throw new NotFoundException('Workflow execution not found.');
    }

    public function retryExecution(int $executionId, int $userId): WorkflowExecution
    {
        $execution = $this->execution($executionId);
        $attrs = $execution->toArray();
        if (!in_array($execution->status(), ['failed', 'partial', 'cancelled'], true)) {
            throw new ValidationException([[
                'code' => 'invalid_status',
                'detail' => 'Only failed, partial, or cancelled executions can be retried.',
            ]]);
        }

        $retried = $this->executions->update($executionId, [
            'status' => 'pending',
            'attempt_number' => (int) ($attrs['attempt_number'] ?? 1) + 1,
            'next_retry_at' => null,
            'error_message' => null,
            'finished_at' => null,
        ]) ?? $execution;

        return $this->engine->execute($retried, false);
    }

    public function cancelExecution(int $executionId, int $userId): WorkflowExecution
    {
        $execution = $this->execution($executionId);
        if (!in_array($execution->status(), ['pending', 'scheduled', 'paused'], true)) {
            throw new ValidationException([[
                'code' => 'invalid_status',
                'detail' => 'Only pending, scheduled, or paused executions can be cancelled.',
            ]]);
        }

        return $this->executions->update($executionId, [
            'status' => 'cancelled',
            'finished_at' => date('Y-m-d H:i:s'),
            'error_message' => 'Cancelled by user #' . $userId,
        ]) ?? $execution;
    }

    public function pauseExecution(int $executionId, int $userId): WorkflowExecution
    {
        $execution = $this->execution($executionId);
        if ($execution->status() !== 'scheduled') {
            throw new ValidationException([[
                'code' => 'invalid_status',
                'detail' => 'Only scheduled executions can be paused.',
            ]]);
        }

        return $this->executions->update($executionId, [
            'status' => 'paused',
        ]) ?? $execution;
    }

    public function resumeExecution(int $executionId, int $userId): WorkflowExecution
    {
        $execution = $this->execution($executionId);
        if ($execution->status() !== 'paused') {
            throw new ValidationException([[
                'code' => 'invalid_status',
                'detail' => 'Only paused executions can be resumed.',
            ]]);
        }
        $scheduledAt = $execution->toArray()['scheduled_at'] ?? date('Y-m-d H:i:s');

        return $this->executions->update($executionId, [
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]) ?? $execution;
    }

    public function dashboard(): array
    {
        $stats = $this->workflows->dashboardStats();
        $stats['recent_executions'] = $this->executions->recent(12);

        return $stats;
    }

    public function catalogs(): array
    {
        $events = [];
        try {
            $result = $this->eventRegistry->list(1, 200, null);
            $items = $result['items'] ?? $result;
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (is_object($item) && method_exists($item, 'toArray')) {
                        $events[] = $item->toArray();
                    } elseif (is_array($item)) {
                        $events[] = $item;
                    }
                }
            }
        } catch (\Throwable) {
            $events = [];
        }

        return array_merge($this->catalog->toArray(), [
            'triggers' => $events,
        ]);
    }

    public function processScheduler(): int
    {
        return $this->scheduler->processDue();
    }

    private function requireWorkflow(int $id): Workflow
    {
        return $this->workflows->find($id)
            ?? throw new NotFoundException('Workflow not found.');
    }

    private function resolveVersion(Workflow $workflow, ?int $versionId): WorkflowVersion
    {
        if ($versionId !== null) {
            $version = $this->versions->find($versionId);
            if ($version === null || $version->workflowId() !== $workflow->id()) {
                throw new NotFoundException('Workflow version not found.');
            }

            return $version;
        }
        $activeId = $workflow->activeVersionId();
        if ($activeId === null) {
            throw new ValidationException([[
                'code' => 'no_version',
                'detail' => 'Workflow has no active version.',
            ]]);
        }

        return $this->versions->find($activeId)
            ?? throw new NotFoundException('Active workflow version not found.');
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function normalizeDefinition(
        array $definition,
        string $scheduleMode,
        ?string $cronExpression,
        int $delaySeconds,
    ): array {
        $trigger = is_array($definition['trigger'] ?? null) ? $definition['trigger'] : [];
        $conditions = is_array($definition['conditions'] ?? null) ? $definition['conditions'] : ['logic' => 'AND', 'rules' => []];
        $actions = is_array($definition['actions'] ?? null) ? $definition['actions'] : [];
        $schedule = is_array($definition['schedule'] ?? null) ? $definition['schedule'] : [];
        $schedule['mode'] = $scheduleMode;
        $schedule['delay_seconds'] = $delaySeconds;
        if ($cronExpression !== null) {
            $schedule['cron'] = $cronExpression;
        }

        return [
            'trigger' => [
                'event_key' => (string) ($trigger['event_key'] ?? ''),
                'source_module' => (string) ($trigger['source_module'] ?? 'system'),
                'filter' => $trigger['filter'] ?? null,
            ],
            'conditions' => $conditions,
            'actions' => array_values(array_map(static function (array $action, int $index): array {
                return [
                    'type' => (string) ($action['type'] ?? $action['action_type'] ?? ''),
                    'name' => $action['name'] ?? null,
                    'config' => is_array($action['config'] ?? null)
                        ? $action['config']
                        : (is_array($action['config_json'] ?? null) ? $action['config_json'] : []),
                    'order' => (int) ($action['order'] ?? $action['sort_order'] ?? $index + 1),
                    'continue_on_failure' => (bool) ($action['continue_on_failure'] ?? false),
                    'is_enabled' => (bool) ($action['is_enabled'] ?? true),
                ];
            }, $actions, array_keys($actions))),
            'schedule' => $schedule,
        ];
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function createVersionFromDefinition(
        int $workflowId,
        int $versionNumber,
        array $definition,
        ?string $changelog,
        int $userId,
    ): WorkflowVersion {
        $trigger = $definition['trigger'] ?? [];
        $triggers = [[
            'event_key' => (string) ($trigger['event_key'] ?? ''),
            'source_module' => (string) ($trigger['source_module'] ?? 'system'),
            'filter_json' => $trigger['filter'] ?? null,
            'is_active' => true,
        ]];
        $conditions = [];
        if (isset($definition['conditions']) && is_array($definition['conditions'])) {
            $conditions = [$definition['conditions']];
        }
        $actions = is_array($definition['actions'] ?? null) ? $definition['actions'] : [];

        return $this->versions->createVersion(
            $workflowId,
            $versionNumber,
            $definition,
            $triggers,
            $conditions,
            $actions,
            $changelog,
            $userId,
            true,
        );
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
