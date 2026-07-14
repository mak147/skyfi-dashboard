<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Repositories;

use PDO;
use SkyFi\Monitoring\Contracts\SyncEventRepositoryContract;
use SkyFi\Monitoring\DomainModels\SyncEventLog;
use SkyFi\Monitoring\DTOs\SyncEventLogData;

final class PdoSyncEventRepository implements SyncEventRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function recordEvent(SyncEventLogData $data): SyncEventLog
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO monitoring_sync_events (router_id, sync_type, status, items_synced, error_message, execution_time_ms, created_at)
             VALUES (:router_id, :sync_type, :status, :items_synced, :error_message, :execution_time_ms, :created_at)'
        );
        $createdAt = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'router_id' => $data->routerId,
            'sync_type' => $data->syncType,
            'status' => $data->status,
            'items_synced' => $data->itemsSynced,
            'error_message' => $data->errorMessage !== null ? substr($data->errorMessage, 0, 500) : null,
            'execution_time_ms' => $data->executionTimeMs,
            'created_at' => $createdAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? throw new \RuntimeException('Failed to retrieve sync event record.');
    }

    /** @return array{items: array<int, SyncEventLog>, total: int, page: int, per_page: int} */
    public function listEvents(int $page = 1, int $perPage = 25, ?int $routerId = null, ?string $syncType = null): array
    {
        $conditions = [];
        $params = [];

        if ($routerId !== null && $routerId > 0) {
            $conditions[] = 'router_id = :router_id';
            $params['router_id'] = $routerId;
        }
        if ($syncType !== null && $syncType !== '') {
            $conditions[] = 'sync_type = :sync_type';
            $params['sync_type'] = $syncType;
        }

        $whereClause = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM monitoring_sync_events {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare(
            "SELECT * FROM monitoring_sync_events {$whereClause} ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = SyncEventLog::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    private function find(int $id): ?SyncEventLog
    {
        $stmt = $this->pdo->prepare('SELECT * FROM monitoring_sync_events WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : SyncEventLog::fromRow($row);
    }
}
