<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Repositories;

use PDO;
use SkyFi\Workflow\Contracts\WorkflowRepositoryContract;
use SkyFi\Workflow\DomainModels\Workflow;
use SkyFi\Workflow\DTOs\WorkflowListFilters;

final class PdoWorkflowRepository implements WorkflowRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(WorkflowListFilters $filters): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null) {
            $where[] = '(name LIKE :search OR description LIKE :search OR trigger_event_key LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }
        if ($filters->status !== null) {
            $where[] = 'status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->triggerEventKey !== null) {
            $where[] = 'trigger_event_key = :trigger_event_key';
            $params['trigger_event_key'] = $filters->triggerEventKey;
        }
        if ($filters->isEnabled !== null) {
            $where[] = 'is_enabled = :is_enabled';
            $params['is_enabled'] = (int) $filters->isEnabled;
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM workflows WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM workflows WHERE {$whereSql} ORDER BY updated_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = array_map(
            static fn (array $row): Workflow => Workflow::fromRow($row),
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

    public function find(int $id): ?Workflow
    {
        $stmt = $this->pdo->prepare('SELECT * FROM workflows WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Workflow::fromRow($row) : null;
    }

    public function findByUuid(string $uuid): ?Workflow
    {
        $stmt = $this->pdo->prepare('SELECT * FROM workflows WHERE uuid = :uuid AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Workflow::fromRow($row) : null;
    }

    public function findEnabledByEvent(string $eventKey): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM workflows
             WHERE deleted_at IS NULL
               AND is_enabled = 1
               AND status = 'active'
               AND trigger_event_key = :event_key"
        );
        $stmt->execute(['event_key' => $eventKey]);

        return array_map(
            static fn (array $row): Workflow => Workflow::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        );
    }

    public function create(array $data): Workflow
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO workflows (
                uuid, name, description, status, is_enabled, active_version_id, trigger_event_key,
                schedule_mode, cron_expression, delay_seconds, max_retries, retry_delay_seconds,
                created_by, updated_by
            ) VALUES (
                :uuid, :name, :description, :status, :is_enabled, :active_version_id, :trigger_event_key,
                :schedule_mode, :cron_expression, :delay_seconds, :max_retries, :retry_delay_seconds,
                :created_by, :updated_by
            )'
        );
        $stmt->execute([
            'uuid' => $data['uuid'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'is_enabled' => (int) ($data['is_enabled'] ?? false),
            'active_version_id' => $data['active_version_id'] ?? null,
            'trigger_event_key' => $data['trigger_event_key'] ?? null,
            'schedule_mode' => $data['schedule_mode'] ?? 'immediate',
            'cron_expression' => $data['cron_expression'] ?? null,
            'delay_seconds' => (int) ($data['delay_seconds'] ?? 0),
            'max_retries' => (int) ($data['max_retries'] ?? 0),
            'retry_delay_seconds' => (int) ($data['retry_delay_seconds'] ?? 60),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ]);

        return $this->find((int) $this->pdo->lastInsertId())
            ?? throw new \RuntimeException('Failed to create workflow.');
    }

    public function update(int $id, array $data): ?Workflow
    {
        if ($data === []) {
            return $this->find($id);
        }

        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if (in_array($key, [
                'name', 'description', 'status', 'is_enabled', 'active_version_id', 'trigger_event_key',
                'schedule_mode', 'cron_expression', 'delay_seconds', 'max_retries', 'retry_delay_seconds',
                'updated_by', 'last_executed_at', 'execution_count', 'success_count', 'failure_count',
            ], true)) {
                $fields[] = "{$key} = :{$key}";
                if (is_bool($value)) {
                    $params[$key] = (int) $value;
                } else {
                    $params[$key] = $value;
                }
            }
        }
        if ($fields === []) {
            return $this->find($id);
        }

        $sql = 'UPDATE workflows SET ' . implode(', ', $fields) . ' WHERE id = :id AND deleted_at IS NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->find($id);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE workflows SET deleted_at = CURRENT_TIMESTAMP, is_enabled = 0, status = \'disabled\' WHERE id = :id AND deleted_at IS NULL'
        );

        return $stmt->execute(['id' => $id]) && $stmt->rowCount() > 0;
    }

    public function recordExecutionStats(int $id, bool $success): void
    {
        $column = $success ? 'success_count' : 'failure_count';
        $stmt = $this->pdo->prepare(
            "UPDATE workflows
             SET execution_count = execution_count + 1,
                 {$column} = {$column} + 1,
                 last_executed_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public function dashboardStats(): array
    {
        $totals = $this->pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN is_enabled = 1 AND status = 'active' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) AS paused,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft,
                SUM(CASE WHEN status = 'disabled' THEN 1 ELSE 0 END) AS disabled,
                COALESCE(SUM(execution_count), 0) AS total_executions,
                COALESCE(SUM(success_count), 0) AS total_success,
                COALESCE(SUM(failure_count), 0) AS total_failures
             FROM workflows WHERE deleted_at IS NULL"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        $exec = $this->pdo->query(
            "SELECT
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success_24h,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_24h,
                SUM(CASE WHEN status IN ('pending','scheduled','running') THEN 1 ELSE 0 END) AS in_flight,
                COUNT(*) AS executions_24h
             FROM workflow_executions
             WHERE created_at >= (NOW() - INTERVAL 1 DAY)"
        )->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'workflows' => [
                'total' => (int) ($totals['total'] ?? 0),
                'active' => (int) ($totals['active'] ?? 0),
                'paused' => (int) ($totals['paused'] ?? 0),
                'draft' => (int) ($totals['draft'] ?? 0),
                'disabled' => (int) ($totals['disabled'] ?? 0),
            ],
            'lifetime' => [
                'executions' => (int) ($totals['total_executions'] ?? 0),
                'success' => (int) ($totals['total_success'] ?? 0),
                'failures' => (int) ($totals['total_failures'] ?? 0),
            ],
            'last_24h' => [
                'executions' => (int) ($exec['executions_24h'] ?? 0),
                'success' => (int) ($exec['success_24h'] ?? 0),
                'failed' => (int) ($exec['failed_24h'] ?? 0),
                'in_flight' => (int) ($exec['in_flight'] ?? 0),
            ],
        ];
    }
}
