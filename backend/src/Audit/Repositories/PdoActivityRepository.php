<?php

declare(strict_types=1);

namespace SkyFi\Audit\Repositories;

use PDO;
use SkyFi\Audit\Contracts\ActivityRepositoryContract;
use SkyFi\Audit\DomainModels\ActivityEvent;
use SkyFi\Audit\DTOs\ActivityFilters;

final class PdoActivityRepository implements ActivityRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function search(ActivityFilters $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if ($filters->userId !== null) {
            $where[] = 'a.user_id = ?';
            $params[] = $filters->userId;
        }
        if ($filters->module !== null) {
            $where[] = 'a.module = ?';
            $params[] = $filters->module;
        }
        if ($filters->action !== null) {
            $where[] = 'a.action LIKE ?';
            $params[] = '%' . $filters->action . '%';
        }
        if ($filters->resourceType !== null) {
            $where[] = 'a.resource_type = ?';
            $params[] = $filters->resourceType;
        }
        if ($filters->resourceId !== null) {
            $where[] = 'a.resource_id = ?';
            $params[] = $filters->resourceId;
        }
        if ($filters->dateFrom !== null) {
            $where[] = 'a.created_at >= ?';
            $params[] = $filters->dateFrom . ' 00:00:00';
        }
        if ($filters->dateTo !== null) {
            $where[] = 'a.created_at <= ?';
            $params[] = $filters->dateTo . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM activity_events a WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        $offset = ($filters->page - 1) * $filters->perPage;

        $sql = "SELECT a.*, u.name as user_name, u.email as user_email
                FROM activity_events a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $items = array_map(
            static fn(array $row): ActivityEvent => ActivityEvent::fromRow($row),
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

    public function create(array $data): ActivityEvent
    {
        $sql = "INSERT INTO activity_events (
                    user_id, module, action, resource_type, resource_id,
                    description, ip_address, user_agent, metadata, correlation_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['module'] ?? '',
            $data['action'] ?? '',
            $data['resource_type'] ?? '',
            $data['resource_id'] ?? null,
            $data['description'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            isset($data['metadata']) ? json_encode($data['metadata'], JSON_THROW_ON_ERROR) : null,
            $data['correlation_id'] ?? null,
        ]);

        $id = (int) $this->pdo->lastInsertId();

        $findSql = "SELECT a.*, u.name as user_name, u.email as user_email
                    FROM activity_events a
                    LEFT JOIN users u ON u.id = a.user_id
                    WHERE a.id = ?";
        $stmt = $this->pdo->prepare($findSql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return ActivityEvent::fromRow($row);
        }
        return ActivityEvent::fromRow(['id' => $id]);
    }
}
