<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Repositories;

use PDO;
use SkyFi\Hotspot\Contracts\HotspotSessionRepositoryContract;
use SkyFi\Hotspot\DomainModels\HotspotSessionHistory;

final class PdoHotspotSessionRepository implements HotspotSessionRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listHistory(int $page = 1, int $perPage = 15, ?int $userId = null, ?int $routerId = null, ?string $username = null): array
    {
        $where = ['1=1'];
        $params = [];

        if ($userId !== null && $userId > 0) {
            $where[] = 'h.hotspot_user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($routerId !== null && $routerId > 0) {
            $where[] = 'h.router_id = :router_id';
            $params['router_id'] = $routerId;
        }

        if ($username !== null && $username !== '') {
            $where[] = 'h.username LIKE :username';
            $params['username'] = '%' . $username . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM hotspot_session_history h WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $selectStmt = $this->pdo->prepare(
            "SELECT h.* FROM hotspot_session_history h WHERE {$whereSql} ORDER BY h.started_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $selectStmt->execute($params);

        $items = [];
        while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = HotspotSessionHistory::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function logSessionHistory(array $data): HotspotSessionHistory
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn ($k): string => ":{$k}", array_keys($data)));

        $stmt = $this->pdo->prepare("INSERT INTO hotspot_session_history ({$columns}) VALUES ({$placeholders})");
        $stmt->execute($data);

        $id = (int) $this->pdo->lastInsertId();
        $selectStmt = $this->pdo->prepare('SELECT * FROM hotspot_session_history WHERE id = :id');
        $selectStmt->execute(['id' => $id]);
        $row = $selectStmt->fetch(PDO::FETCH_ASSOC);

        return HotspotSessionHistory::fromRow($row);
    }

    public function recordLogin(
        int $routerId,
        ?int $hotspotUserId,
        string $username,
        ?string $macAddress,
        ?string $ipAddress,
        string $status,
        ?string $reason
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO hotspot_login_history (
                router_id, hotspot_user_id, username, mac_address, ip_address, status, reason, attempted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $routerId,
            $hotspotUserId,
            $username,
            $macAddress,
            $ipAddress,
            $status,
            $reason,
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function listLoginHistory(int $page = 1, int $perPage = 15, ?int $userId = null, ?int $routerId = null): array
    {
        $where = ['1=1'];
        $params = [];

        if ($userId !== null && $userId > 0) {
            $where[] = 'h.hotspot_user_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($routerId !== null && $routerId > 0) {
            $where[] = 'h.router_id = :router_id';
            $params['router_id'] = $routerId;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM hotspot_login_history h WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $selectStmt = $this->pdo->prepare(
            "SELECT h.*, r.name AS router_name FROM hotspot_login_history h LEFT JOIN mikrotik_routers r ON h.router_id = r.id WHERE {$whereSql} ORDER BY h.attempted_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $selectStmt->execute($params);

        $items = [];
        while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = $row;
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function getUserStatistics(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                COALESCE(SUM(uptime_seconds), 0) AS total_uptime_seconds,
                COALESCE(SUM(bytes_in), 0) AS total_bytes_in,
                COALESCE(SUM(bytes_out), 0) AS total_bytes_out,
                COUNT(*) AS session_count
            FROM hotspot_session_history
            WHERE hotspot_user_id = :user_id
        ');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_uptime_seconds' => (int) ($row['total_uptime_seconds'] ?? 0),
            'total_bytes_in' => (int) ($row['total_bytes_in'] ?? 0),
            'total_bytes_out' => (int) ($row['total_bytes_out'] ?? 0),
            'session_count' => (int) ($row['session_count'] ?? 0),
        ];
    }
}
