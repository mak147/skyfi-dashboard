<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Repositories;

use PDO;
use SkyFi\Workflow\Contracts\WorkflowExecutionRepositoryContract;
use SkyFi\Workflow\DomainModels\WorkflowExecution;
use SkyFi\Workflow\DTOs\ExecutionListFilters;

final class PdoWorkflowExecutionRepository implements WorkflowExecutionRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(ExecutionListFilters $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if ($filters->workflowId !== null) {
            $where[] = 'e.workflow_id = :workflow_id';
            $params['workflow_id'] = $filters->workflowId;
        }
        if ($filters->status !== null) {
            $where[] = 'e.status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->triggerEventKey !== null) {
            $where[] = 'e.trigger_event_key = :trigger_event_key';
            $params['trigger_event_key'] = $filters->triggerEventKey;
        }
        if ($filters->triggerSource !== null) {
            $where[] = 'e.trigger_source = :trigger_source';
            $params['trigger_source'] = $filters->triggerSource;
        }
        if ($filters->search !== null) {
            $where[] = '(e.uuid LIKE :search OR e.trigger_event_key LIKE :search OR e.error_message LIKE :search OR w.name LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }
        if ($filters->from !== null) {
            $where[] = 'e.created_at >= :from';
            $params['from'] = $filters->from;
        }
        if ($filters->to !== null) {
            $where[] = 'e.created_at <= :to';
            $params['to'] = $filters->to;
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare(
            "SELECT COUNT(*) FROM workflow_executions e
             LEFT JOIN workflows w ON w.id = e.workflow_id
             WHERE {$whereSql}"
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT e.*, w.name AS workflow_name
             FROM workflow_executions e
             LEFT JOIN workflows w ON w.id = e.workflow_id
             WHERE {$whereSql}
             ORDER BY e.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = array_map(
            static fn (array $row): WorkflowExecution => WorkflowExecution::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        );

        return [
            'items' => $items,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / max(1, $filters->perPage))),
        ];
    }

    public function find(int $id): ?WorkflowExecution
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, w.name AS workflow_name
             FROM workflow_executions e
             LEFT JOIN workflows w ON w.id = e.workflow_id
             WHERE e.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? WorkflowExecution::fromRow($row) : null;
    }

    public function findByUuid(string $uuid): ?WorkflowExecution
    {
        $stmt = $this->pdo->prepare('SELECT * FROM workflow_executions WHERE uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? WorkflowExecution::fromRow($row) : null;
    }

    public function create(array $data): WorkflowExecution
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO workflow_executions (
                uuid, workflow_id, version_id, trigger_event_key, trigger_payload, trigger_source,
                status, scheduled_at, started_at, finished_at, duration_ms, attempt_number, max_attempts,
                next_retry_at, result_json, action_results, error_message, actor_user_id
            ) VALUES (
                :uuid, :workflow_id, :version_id, :trigger_event_key, :trigger_payload, :trigger_source,
                :status, :scheduled_at, :started_at, :finished_at, :duration_ms, :attempt_number, :max_attempts,
                :next_retry_at, :result_json, :action_results, :error_message, :actor_user_id
            )'
        );
        $stmt->execute([
            'uuid' => $data['uuid'],
            'workflow_id' => $data['workflow_id'],
            'version_id' => $data['version_id'],
            'trigger_event_key' => $data['trigger_event_key'] ?? null,
            'trigger_payload' => isset($data['trigger_payload'])
                ? json_encode($data['trigger_payload'], JSON_THROW_ON_ERROR)
                : null,
            'trigger_source' => $data['trigger_source'] ?? 'event',
            'status' => $data['status'] ?? 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'started_at' => $data['started_at'] ?? null,
            'finished_at' => $data['finished_at'] ?? null,
            'duration_ms' => $data['duration_ms'] ?? null,
            'attempt_number' => (int) ($data['attempt_number'] ?? 1),
            'max_attempts' => (int) ($data['max_attempts'] ?? 1),
            'next_retry_at' => $data['next_retry_at'] ?? null,
            'result_json' => isset($data['result_json'])
                ? json_encode($data['result_json'], JSON_THROW_ON_ERROR)
                : null,
            'action_results' => isset($data['action_results'])
                ? json_encode($data['action_results'], JSON_THROW_ON_ERROR)
                : null,
            'error_message' => $data['error_message'] ?? null,
            'actor_user_id' => $data['actor_user_id'] ?? null,
        ]);

        return $this->find((int) $this->pdo->lastInsertId())
            ?? throw new \RuntimeException('Failed to create workflow execution.');
    }

    public function update(int $id, array $data): ?WorkflowExecution
    {
        if ($data === []) {
            return $this->find($id);
        }

        $allowed = [
            'status', 'scheduled_at', 'started_at', 'finished_at', 'duration_ms', 'attempt_number',
            'max_attempts', 'next_retry_at', 'result_json', 'action_results', 'error_message',
        ];
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $fields[] = "{$key} = :{$key}";
            if (in_array($key, ['result_json', 'action_results'], true) && is_array($value)) {
                $params[$key] = json_encode($value, JSON_THROW_ON_ERROR);
            } else {
                $params[$key] = $value;
            }
        }
        if ($fields === []) {
            return $this->find($id);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE workflow_executions SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $stmt->execute($params);

        return $this->find($id);
    }

    public function findDueScheduled(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM workflow_executions
             WHERE status = 'scheduled'
               AND scheduled_at IS NOT NULL
               AND scheduled_at <= NOW()
             ORDER BY scheduled_at ASC
             LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $row): WorkflowExecution => WorkflowExecution::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        );
    }

    public function findDueRetries(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM workflow_executions
             WHERE status = 'failed'
               AND next_retry_at IS NOT NULL
               AND next_retry_at <= NOW()
               AND attempt_number < max_attempts
             ORDER BY next_retry_at ASC
             LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $row): WorkflowExecution => WorkflowExecution::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        );
    }

    public function recent(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, w.name AS workflow_name
             FROM workflow_executions e
             LEFT JOIN workflows w ON w.id = e.workflow_id
             ORDER BY e.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            foreach (['trigger_payload', 'result_json', 'action_results'] as $jsonField) {
                if (isset($row[$jsonField]) && is_string($row[$jsonField])) {
                    $row[$jsonField] = json_decode($row[$jsonField], true);
                }
            }
            $row['id'] = (int) $row['id'];
            $row['workflow_id'] = (int) $row['workflow_id'];
        }

        return $rows;
    }
}
