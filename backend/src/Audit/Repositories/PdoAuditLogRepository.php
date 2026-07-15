<?php

declare(strict_types=1);

namespace SkyFi\Audit\Repositories;

use PDO;
use SkyFi\Audit\Contracts\AuditLogRepositoryContract;
use SkyFi\Audit\DomainModels\AuditLog;
use SkyFi\Audit\DTOs\AuditLogFilters;

final class PdoAuditLogRepository implements AuditLogRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function search(AuditLogFilters $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if ($filters->module !== null) {
            $where[] = 'a.module = ?';
            $params[] = $filters->module;
        }
        if ($filters->action !== null) {
            $where[] = 'a.action LIKE ?';
            $params[] = '%' . $filters->action . '%';
        }
        if ($filters->entityType !== null) {
            $where[] = 'a.entity_type = ?';
            $params[] = $filters->entityType;
        }
        if ($filters->entityId !== null) {
            $where[] = 'a.entity_id = ?';
            $params[] = $filters->entityId;
        }
        if ($filters->userId !== null) {
            $where[] = 'a.user_id = ?';
            $params[] = $filters->userId;
        }
        if ($filters->severity !== null) {
            $where[] = 'a.severity = ?';
            $params[] = $filters->severity;
        }
        if ($filters->correlationId !== null) {
            $where[] = 'a.correlation_id = ?';
            $params[] = $filters->correlationId;
        }
        if ($filters->dateFrom !== null) {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters->dateFrom . ' 00:00:00';
        }
        if ($filters->dateTo !== null) {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters->dateTo . ' 23:59:59';
        }
        if ($filters->search !== null) {
            $where[] = '(a.action LIKE ? OR a.entity_type LIKE ? OR a.module LIKE ?)';
            $search = '%' . $filters->search . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM audit_logs a WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        $offset = ($filters->page - 1) * $filters->perPage;

        $sql = "SELECT a.*, u.name as user_name, u.email as user_email
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = array_map(
            static fn(array $row): AuditLog => AuditLog::fromRow($row),
            $stmt->fetchAll() ?: [],
        );

        return [
            'items' => $items,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'total' => $total,
            'lastPage' => $lastPage,
        ];
    }

    public function find(int $id): ?AuditLog
    {
        $sql = "SELECT a.*, u.name as user_name, u.email as user_email
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE a.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return AuditLog::fromRow($row);
    }

    public function create(array $data): AuditLog
    {
        $sql = "INSERT INTO audit_logs (
                    user_id, action, entity_type, entity_id, module, resource, severity,
                    correlation_id, old_values, new_values, ip_address, user_agent, url,
                    compliance_tags, is_immutable, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['action'] ?? '',
            $data['entity_type'] ?? '',
            $data['entity_id'] ?? null,
            $data['module'] ?? null,
            $data['resource'] ?? null,
            $data['severity'] ?? 'info',
            $data['correlation_id'] ?? null,
            isset($data['old_values']) ? json_encode($data['old_values'], JSON_THROW_ON_ERROR) : null,
            isset($data['new_values']) ? json_encode($data['new_values'], JSON_THROW_ON_ERROR) : null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            $data['url'] ?? null,
            isset($data['compliance_tags']) ? json_encode($data['compliance_tags'], JSON_THROW_ON_ERROR) : null,
            $data['is_immutable'] ?? 0,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? AuditLog::fromRow(['id' => $id]);
    }

    public function getDashboardStats(): array
    {
        $today = date('Y-m-d');
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $monthAgo = date('Y-m-d', strtotime('-30 days'));

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM audit_logs');
        $total = (int) ($stmt ? $stmt->fetchColumn() : 0);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE created_at >= ?");
        $stmt->execute([$today . ' 00:00:00']);
        $todayCount = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE created_at >= ?");
        $stmt->execute([$weekAgo . ' 00:00:00']);
        $weekCount = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE created_at >= ?");
        $stmt->execute([$monthAgo . ' 00:00:00']);
        $monthCount = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM audit_logs WHERE severity = 'critical'");
        $criticalCount = (int) ($stmt ? $stmt->fetchColumn() : 0);

        $moduleStmt = $this->pdo->query("SELECT COALESCE(module, 'unknown') as module, COUNT(*) as count FROM audit_logs GROUP BY module ORDER BY count DESC LIMIT 10");
        $byModule = $moduleStmt ? $moduleStmt->fetchAll() : [];

        $actionStmt = $this->pdo->query("SELECT action, COUNT(*) as count FROM audit_logs GROUP BY action ORDER BY count DESC LIMIT 10");
        $topActions = $actionStmt ? $actionStmt->fetchAll() : [];

        $recentStmt = $this->pdo->query("SELECT a.*, u.name as user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 10");
        $recentActivity = $recentStmt ? array_map(static fn(array $row): AuditLog => AuditLog::fromRow($row), $recentStmt->fetchAll() ?: []) : [];

        return [
            'total_logs' => $total,
            'today_count' => $todayCount,
            'week_count' => $weekCount,
            'month_count' => $monthCount,
            'critical_count' => $criticalCount,
            'by_module' => $byModule,
            'top_actions' => $topActions,
            'recent_activity' => array_map(static fn(AuditLog $l) => $l->toArray(), $recentActivity),
        ];
    }

    public function getDistinctModules(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT module FROM audit_logs WHERE module IS NOT NULL ORDER BY module");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    public function getDistinctActions(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    public function getDistinctEntityTypes(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
}
