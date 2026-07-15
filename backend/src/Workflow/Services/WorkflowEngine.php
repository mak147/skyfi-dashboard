<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Services;

use SkyFi\Workflow\Contracts\ActionDispatcherContract;
use SkyFi\Workflow\Contracts\RuleEvaluatorContract;
use SkyFi\Workflow\Contracts\WorkflowEngineContract;
use SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract;
use SkyFi\Workflow\Contracts\WorkflowRepositoryContract;
use SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract;
use SkyFi\Workflow\DomainModels\Workflow;
use SkyFi\Workflow\DomainModels\WorkflowExecution;
use SkyFi\Workflow\DomainModels\WorkflowVersion;

final class WorkflowEngine implements WorkflowEngineContract
{
    public function __construct(
        private readonly WorkflowRepositoryContract $workflows,
        private readonly WorkflowVersionRepositoryContract $versions,
        private readonly WorkflowExecutionRepositoryContract $executions,
        private readonly RuleEvaluatorContract $evaluator,
        private readonly ActionDispatcherContract $dispatcher,
    ) {}

    public function enqueue(
        Workflow $workflow,
        WorkflowVersion $version,
        array $payload,
        string $source,
        ?string $eventKey = null,
        ?int $actorUserId = null,
        bool $dryRun = false,
    ): WorkflowExecution {
        $definition = $version->definition();
        $conditions = is_array($definition['conditions'] ?? null) ? $definition['conditions'] : null;
        $passes = $this->evaluator->evaluate($conditions, $payload);

        $maxAttempts = max(1, $workflow->maxRetries() + 1);
        $scheduleMode = $workflow->scheduleMode();
        $status = 'pending';
        $scheduledAt = null;

        if (!$passes) {
            return $this->executions->create([
                'uuid' => $this->uuid(),
                'workflow_id' => $workflow->id(),
                'version_id' => $version->id(),
                'trigger_event_key' => $eventKey ?? $workflow->triggerEventKey(),
                'trigger_payload' => $payload,
                'trigger_source' => $source,
                'status' => 'skipped',
                'started_at' => date('Y-m-d H:i:s'),
                'finished_at' => date('Y-m-d H:i:s'),
                'duration_ms' => 0,
                'attempt_number' => 1,
                'max_attempts' => $maxAttempts,
                'result_json' => ['reason' => 'Conditions not met'],
                'action_results' => [],
                'actor_user_id' => $actorUserId,
            ]);
        }

        if (!$dryRun && in_array($scheduleMode, ['delayed', 'cron', 'recurring'], true)) {
            $status = 'scheduled';
            $scheduledAt = $this->resolveScheduledAt($workflow);
        }

        $execution = $this->executions->create([
            'uuid' => $this->uuid(),
            'workflow_id' => $workflow->id(),
            'version_id' => $version->id(),
            'trigger_event_key' => $eventKey ?? $workflow->triggerEventKey(),
            'trigger_payload' => $payload,
            'trigger_source' => $source,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'attempt_number' => 1,
            'max_attempts' => $maxAttempts,
            'actor_user_id' => $actorUserId,
        ]);

        if ($status === 'scheduled') {
            return $execution;
        }

        return $this->execute($execution, $dryRun);
    }

    public function execute(WorkflowExecution $execution, bool $dryRun = false): WorkflowExecution
    {
        $workflow = $this->workflows->find($execution->workflowId());
        $version = $this->versions->find($execution->versionId());
        if ($workflow === null || $version === null) {
            return $this->executions->update($execution->id(), [
                'status' => 'failed',
                'error_message' => 'Workflow or version missing for execution.',
                'finished_at' => date('Y-m-d H:i:s'),
            ]) ?? $execution;
        }

        $startedAt = microtime(true);
        $this->executions->update($execution->id(), [
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s'),
            'error_message' => null,
        ]);

        $definition = $version->definition();
        $actions = is_array($definition['actions'] ?? null) ? $definition['actions'] : [];
        if ($actions === []) {
            $actions = $this->versions->actionsForVersion($version->id());
            $actions = array_map(static function (array $row): array {
                return [
                    'type' => $row['action_type'],
                    'name' => $row['name'],
                    'config' => $row['config_json'] ?? [],
                    'order' => $row['sort_order'],
                    'continue_on_failure' => $row['continue_on_failure'] ?? false,
                    'is_enabled' => $row['is_enabled'] ?? true,
                ];
            }, $actions);
        }

        usort($actions, static function (array $a, array $b): int {
            return ((int) ($a['order'] ?? $a['sort_order'] ?? 0)) <=> ((int) ($b['order'] ?? $b['sort_order'] ?? 0));
        });

        $payload = $execution->triggerPayload() ?? [];
        $actorUserId = $execution->toArray()['actor_user_id'] ?? null;
        $actorUserId = $actorUserId !== null ? (int) $actorUserId : null;
        $actionResults = [];
        $successCount = 0;
        $failureCount = 0;
        $fatalError = null;

        foreach ($actions as $action) {
            if (isset($action['is_enabled']) && !(bool) $action['is_enabled']) {
                $actionResults[] = [
                    'action' => $action['type'] ?? $action['action_type'] ?? 'unknown',
                    'status' => 'skipped',
                    'result' => ['reason' => 'Action disabled'],
                ];
                continue;
            }

            $result = $this->dispatcher->dispatch($action, $payload, $actorUserId, $dryRun);
            $actionResults[] = $result;
            if (($result['status'] ?? '') === 'success' || ($result['status'] ?? '') === 'dry_run') {
                $successCount++;
            } else {
                $failureCount++;
                $continue = (bool) ($action['continue_on_failure'] ?? false);
                if (!$continue) {
                    $fatalError = (string) ($result['error'] ?? 'Action failed');
                    break;
                }
            }
        }

        $duration = (int) ((microtime(true) - $startedAt) * 1000);
        $status = 'success';
        if ($failureCount > 0 && $successCount > 0) {
            $status = 'partial';
        } elseif ($failureCount > 0) {
            $status = 'failed';
        }
        if ($dryRun && $status === 'success') {
            // keep success for dry-run completion
        }

        $attempt = (int) ($execution->toArray()['attempt_number'] ?? 1);
        $maxAttempts = (int) ($execution->toArray()['max_attempts'] ?? 1);
        $nextRetryAt = null;
        if ($status === 'failed' && $attempt < $maxAttempts && !$dryRun) {
            $delay = max(1, $workflow->retryDelaySeconds());
            $nextRetryAt = date('Y-m-d H:i:s', time() + $delay);
        }

        $updated = $this->executions->update($execution->id(), [
            'status' => $status,
            'finished_at' => date('Y-m-d H:i:s'),
            'duration_ms' => $duration,
            'action_results' => $actionResults,
            'result_json' => [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'dry_run' => $dryRun,
            ],
            'error_message' => $fatalError,
            'next_retry_at' => $nextRetryAt,
        ]) ?? $execution;

        if (!$dryRun) {
            $this->workflows->recordExecutionStats($workflow->id(), $status === 'success' || $status === 'partial');

            // Recurring: schedule next occurrence after successful completion
            if (
                $workflow->scheduleMode() === 'recurring'
                && in_array($status, ['success', 'partial'], true)
                && $workflow->isEnabled()
                && $workflow->status() === 'active'
            ) {
                $this->executions->create([
                    'uuid' => $this->uuid(),
                    'workflow_id' => $workflow->id(),
                    'version_id' => $version->id(),
                    'trigger_event_key' => $workflow->triggerEventKey(),
                    'trigger_payload' => $payload,
                    'trigger_source' => 'recurring',
                    'status' => 'scheduled',
                    'scheduled_at' => $this->resolveScheduledAt($workflow),
                    'attempt_number' => 1,
                    'max_attempts' => max(1, $workflow->maxRetries() + 1),
                    'actor_user_id' => $actorUserId,
                ]);
            }
        }

        return $updated;
    }

    private function resolveScheduledAt(Workflow $workflow): string
    {
        $mode = $workflow->scheduleMode();
        if ($mode === 'delayed') {
            return date('Y-m-d H:i:s', time() + max(0, $workflow->delaySeconds()));
        }

        $cron = $workflow->cronExpression() ?? '0 * * * *';
        $next = $this->nextCronTime($cron);

        return date('Y-m-d H:i:s', $next);
    }

    /**
     * Minimal 5-field cron next-run calculator (minute hour day month weekday).
     * Supports "*", ranges (1-5), lists (1,2,3), and steps (*/n).
     */
    private function nextCronTime(string $expression, ?int $from = null): int
    {
        $from = $from ?? time();
        $parts = preg_split('/\s+/', trim($expression)) ?: [];
        if (count($parts) !== 5) {
            return $from + 3600;
        }
        [$minExpr, $hourExpr, $domExpr, $monExpr, $dowExpr] = $parts;
        $cursor = $from + 60 - ($from % 60);
        $limit = $cursor + (8 * 24 * 3600);
        while ($cursor <= $limit) {
            $minute = (int) date('i', $cursor);
            $hour = (int) date('G', $cursor);
            $dom = (int) date('j', $cursor);
            $mon = (int) date('n', $cursor);
            $dow = (int) date('w', $cursor);
            if (
                $this->cronMatch($minExpr, $minute, 0, 59)
                && $this->cronMatch($hourExpr, $hour, 0, 23)
                && $this->cronMatch($domExpr, $dom, 1, 31)
                && $this->cronMatch($monExpr, $mon, 1, 12)
                && $this->cronMatch($dowExpr, $dow, 0, 6)
            ) {
                return $cursor;
            }
            $cursor += 60;
        }

        return $from + 3600;
    }

    private function cronMatch(string $expr, int $value, int $min, int $max): bool
    {
        if ($expr === '*') {
            return true;
        }
        foreach (explode(',', $expr) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $step = 1;
            if (str_contains($part, '/')) {
                [$part, $stepRaw] = explode('/', $part, 2);
                $step = max(1, (int) $stepRaw);
            }
            if ($part === '*') {
                if (($value - $min) % $step === 0) {
                    return true;
                }
                continue;
            }
            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part, 2));
                if ($value >= $start && $value <= $end && (($value - $start) % $step === 0)) {
                    return true;
                }
                continue;
            }
            if ((int) $part === $value) {
                return true;
            }
        }

        return false;
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
