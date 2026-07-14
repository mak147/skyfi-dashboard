<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Repositories;

use PDO;
use SkyFi\Pppoe\Contracts\PppoeSyncLoggerContract;

final class PdoPppoeSyncLogger implements PppoeSyncLoggerContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function log(
        int $routerId,
        ?int $accountId,
        string $action,
        string $status,
        string $message,
        ?array $diffPayload = null,
        ?int $createdBy = null
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO pppoe_sync_logs (
                router_id, account_id, action, status, message, diff_payload, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $routerId,
            $accountId,
            $action,
            $status,
            $message,
            $diffPayload !== null ? json_encode($diffPayload, JSON_THROW_ON_ERROR) : null,
            $createdBy,
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function listRecent(int $limit = 50, ?int $routerId = null, ?int $accountId = null): array
    {
        $where = ['1=1'];
        $params = [];

        if ($routerId !== null && $routerId > 0) {
            $where[] = 'l.router_id = :router_id';
            $params['router_id'] = $routerId;
        }

        if ($accountId !== null && $accountId > 0) {
            $where[] = 'l.account_id = :account_id';
            $params['account_id'] = $accountId;
        }

        $whereSql = implode(' AND ', $where);
        $safeLimit = max(1, min(200, $limit));

        $stmt = $this->pdo->prepare("
            SELECT l.*, r.name AS router_name, a.username AS account_username
            FROM pppoe_sync_logs l
            LEFT JOIN mikrotik_routers r ON l.router_id = r.id
            LEFT JOIN pppoe_accounts a ON l.account_id = a.id
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
