<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Repositories;

use PDO;
use SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract;
use SkyFi\Workflow\DomainModels\WorkflowVersion;

final class PdoWorkflowVersionRepository implements WorkflowVersionRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?WorkflowVersion
    {
        $stmt = $this->pdo->prepare('SELECT * FROM workflow_versions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? WorkflowVersion::fromRow($row) : null;
    }

    public function listForWorkflow(int $workflowId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM workflow_versions WHERE workflow_id = :workflow_id ORDER BY version_number DESC'
        );
        $stmt->execute(['workflow_id' => $workflowId]);

        return array_map(
            static fn (array $row): WorkflowVersion => WorkflowVersion::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        );
    }

    public function latestVersionNumber(int $workflowId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(MAX(version_number), 0) FROM workflow_versions WHERE workflow_id = :workflow_id'
        );
        $stmt->execute(['workflow_id' => $workflowId]);

        return (int) $stmt->fetchColumn();
    }

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
    ): WorkflowVersion {
        $stmt = $this->pdo->prepare(
            'INSERT INTO workflow_versions (workflow_id, version_number, definition, changelog, is_published, created_by)
             VALUES (:workflow_id, :version_number, :definition, :changelog, :is_published, :created_by)'
        );
        $stmt->execute([
            'workflow_id' => $workflowId,
            'version_number' => $versionNumber,
            'definition' => json_encode($definition, JSON_THROW_ON_ERROR),
            'changelog' => $changelog,
            'is_published' => (int) $isPublished,
            'created_by' => $createdBy,
        ]);
        $versionId = (int) $this->pdo->lastInsertId();

        $triggerStmt = $this->pdo->prepare(
            'INSERT INTO workflow_triggers (workflow_id, version_id, event_key, source_module, filter_json, is_active)
             VALUES (:workflow_id, :version_id, :event_key, :source_module, :filter_json, :is_active)'
        );
        foreach ($triggers as $trigger) {
            $triggerStmt->execute([
                'workflow_id' => $workflowId,
                'version_id' => $versionId,
                'event_key' => (string) ($trigger['event_key'] ?? ''),
                'source_module' => (string) ($trigger['source_module'] ?? 'system'),
                'filter_json' => isset($trigger['filter_json'])
                    ? json_encode($trigger['filter_json'], JSON_THROW_ON_ERROR)
                    : null,
                'is_active' => (int) ($trigger['is_active'] ?? true),
            ]);
        }

        $conditionStmt = $this->pdo->prepare(
            'INSERT INTO workflow_conditions (
                workflow_id, version_id, parent_id, group_logic, field_path, operator, value_json, sort_order
             ) VALUES (
                :workflow_id, :version_id, :parent_id, :group_logic, :field_path, :operator, :value_json, :sort_order
             )'
        );
        $this->insertConditions($conditionStmt, $workflowId, $versionId, $conditions, null);

        $actionStmt = $this->pdo->prepare(
            'INSERT INTO workflow_actions (
                workflow_id, version_id, action_type, name, config_json, sort_order, continue_on_failure, is_enabled
             ) VALUES (
                :workflow_id, :version_id, :action_type, :name, :config_json, :sort_order, :continue_on_failure, :is_enabled
             )'
        );
        foreach ($actions as $index => $action) {
            $actionStmt->execute([
                'workflow_id' => $workflowId,
                'version_id' => $versionId,
                'action_type' => (string) ($action['type'] ?? $action['action_type'] ?? ''),
                'name' => $action['name'] ?? null,
                'config_json' => json_encode($action['config'] ?? $action['config_json'] ?? [], JSON_THROW_ON_ERROR),
                'sort_order' => (int) ($action['order'] ?? $action['sort_order'] ?? $index + 1),
                'continue_on_failure' => (int) ($action['continue_on_failure'] ?? false),
                'is_enabled' => (int) ($action['is_enabled'] ?? true),
            ]);
        }

        return $this->find($versionId)
            ?? throw new \RuntimeException('Failed to create workflow version.');
    }

    public function triggersForVersion(int $versionId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM workflow_triggers WHERE version_id = :version_id');
        $stmt->execute(['version_id' => $versionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            if (isset($row['filter_json']) && is_string($row['filter_json'])) {
                $row['filter_json'] = json_decode($row['filter_json'], true);
            }
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (bool) $row['is_active'];
        }

        return $rows;
    }

    public function conditionsForVersion(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM workflow_conditions WHERE version_id = :version_id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['version_id' => $versionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            if (isset($row['value_json']) && is_string($row['value_json'])) {
                $row['value_json'] = json_decode($row['value_json'], true);
            }
            $row['id'] = (int) $row['id'];
            $row['parent_id'] = isset($row['parent_id']) ? (int) $row['parent_id'] : null;
            $row['sort_order'] = (int) $row['sort_order'];
        }

        return $rows;
    }

    public function actionsForVersion(int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM workflow_actions WHERE version_id = :version_id ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['version_id' => $versionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            if (isset($row['config_json']) && is_string($row['config_json'])) {
                $row['config_json'] = json_decode($row['config_json'], true) ?: [];
            }
            $row['id'] = (int) $row['id'];
            $row['sort_order'] = (int) $row['sort_order'];
            $row['continue_on_failure'] = (bool) $row['continue_on_failure'];
            $row['is_enabled'] = (bool) $row['is_enabled'];
        }

        return $rows;
    }

    /**
     * @param \PDOStatement $stmt
     * @param list<array<string, mixed>> $conditions
     */
    private function insertConditions(
        \PDOStatement $stmt,
        int $workflowId,
        int $versionId,
        array $conditions,
        ?int $parentId,
    ): void {
        foreach ($conditions as $index => $condition) {
            $isGroup = isset($condition['logic']) || isset($condition['group_logic']) || isset($condition['rules']);
            $stmt->execute([
                'workflow_id' => $workflowId,
                'version_id' => $versionId,
                'parent_id' => $parentId,
                'group_logic' => $isGroup
                    ? strtoupper((string) ($condition['logic'] ?? $condition['group_logic'] ?? 'AND'))
                    : null,
                'field_path' => $isGroup ? null : ($condition['field'] ?? $condition['field_path'] ?? null),
                'operator' => $isGroup ? null : ($condition['operator'] ?? null),
                'value_json' => $isGroup
                    ? null
                    : json_encode(['value' => $condition['value'] ?? null], JSON_THROW_ON_ERROR),
                'sort_order' => (int) ($condition['sort_order'] ?? $index),
            ]);
            $newId = (int) $this->pdo->lastInsertId();
            if ($isGroup && isset($condition['rules']) && is_array($condition['rules'])) {
                $this->insertConditions($stmt, $workflowId, $versionId, $condition['rules'], $newId);
            }
        }
    }
}
