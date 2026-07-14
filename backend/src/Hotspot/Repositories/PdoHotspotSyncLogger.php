<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Repositories;

use PDO;
use SkyFi\Hotspot\Contracts\HotspotSyncLoggerContract;

final class PdoHotspotSyncLogger implements HotspotSyncLoggerContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function log(
        int $routerId,
        ?int $hotspotUserId,
        string $action,
        string $status,
        string $message,
        ?array $diffPayload = null,
        ?int $createdBy = null
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO hotspot_sync_logs (
                router_id, hotspot_user_id, action, status, message, diff_payload, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $routerId,
            $hotspotUserId,
            $action,
            $status,
            $message,
            $diffPayload !== null ? json_encode($diffPayload, JSON_THROW_ON_ERROR) : null,
            $createdBy,
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function listRecent(int $limit = 50, ?int $routerId = null, ?int $hotspotUserId = null): array
    {
        $where = ['1=1'];
        $params = [];

        if ($routerId !== null && $routerId > 0) {
            $where[] = 'l.router_id = :router_id';
            $params['router_id'] = $routerId;
        }

        if ($hotspotUserId !== null && $hotspotUserId > 0) {
            $where[] = 'l.hotspot_user_id = :hotspot_user_id';
            $params['hotspot_user_id'] = $hotspotUserId;
        }

        $whereSql = implode(' AND ', $where);
        $safeLimit = max(1, min(200, $limit));

        $stmt = $this->pdo->prepare("
            SELECT l.*, r.name AS router_name, u.username AS hotspot_username
            FROM hotspot_sync_logs l
            LEFT JOIN mikrotik_routers r ON l.router_id = r.id
            LEFT JOIN hotspot_users u ON l.hotspot_user_id = u.id
            WHERE {$whereSql}
            ORDER BY l.created_at DESC
            LIMIT {$safeLimit}
        ");
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['diff_payload']) && is_string($row['diff_payload']) && $row['diff_payload'] !== '') {
                try {
                    $row['diff_payload'] = json_decode($row['diff_payload'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                    $row['diff_payload'] = null;
                }
            }
            $items[] = $row;
        }

        return $items;
    }
}
