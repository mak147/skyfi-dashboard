<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Services;

use SkyFi\Workflow\Contracts\WorkflowEngineContract;
use SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract;

final class WorkflowScheduler
{
    public function __construct(
        private readonly WorkflowExecutionRepositoryContract $executions,
        private readonly WorkflowEngineContract $engine,
    ) {}

    public function processDue(int $limit = 50): int
    {
        $processed = 0;

        foreach ($this->executions->findDueScheduled($limit) as $execution) {
            try {
                $this->engine->execute($execution, false);
                $processed++;
            } catch (\Throwable $e) {
                $this->executions->update($execution->id(), [
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'finished_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        foreach ($this->executions->findDueRetries($limit) as $execution) {
            $attrs = $execution->toArray();
            $attempt = (int) ($attrs['attempt_number'] ?? 1) + 1;
            $retried = $this->executions->update($execution->id(), [
                'status' => 'pending',
                'attempt_number' => $attempt,
                'next_retry_at' => null,
                'error_message' => null,
            ]);
            if ($retried === null) {
                continue;
            }
            try {
                $this->engine->execute($retried, false);
                $processed++;
            } catch (\Throwable $e) {
                $this->executions->update($retried->id(), [
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'finished_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $processed;
    }
}
