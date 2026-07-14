<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Repositories;

use PDO;
use SkyFi\Pppoe\Contracts\PppoeSessionRepositoryContract;
use SkyFi\Pppoe\DomainModels\PppoeSessionHistory;

final class PdoPppoeSessionRepository implements PppoeSessionRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listHistory(
        int $page = 1,
        int $perPage = 15,
        ?int $accountId = null,
        ?int $routerId = null,
        ?string $username = null
    ): array {
        $where = ['1=1'];
        $params = [];

        if ($accountId !== null && $accountId > 0) {
            $where[] = 'h.account_id = :account_id';
            $params['account_id'] = $accountId;
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

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM pppoe_session_history h WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $selectStmt = $this->pdo->prepare(
            "SELECT h.* FROM pppoe_session_history h WHERE {$whereSql} ORDER BY h.started_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $selectStmt->execute($params);

        $items = [];
        while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = PppoeSessionHistory::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function logSessionHistory(array $data): PppoeSessionHistory
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn ($k): string => ":{$k}", array_keys($data)));

        $stmt = $this->pdo->prepare("INSERT INTO pppoe_session_history ({$columns}) VALUES ({$placeholders})");
        $stmt->execute($data);

        $id = (int) $this->pdo->lastInsertId();
        $selectStmt = $this->pdo->prepare('SELECT * FROM pppoe_session_history WHERE id = :id');
        $selectStmt->execute(['id' => $id]);
        $row = $selectStmt->fetch(PDO::FETCH_ASSOC);

        return PppoeSessionHistory::fromRow($row);
    }

    public function recordAuthentication(
        int $routerId,
        ?int $accountId,
        string $username,
        ?string $callerId,
        ?string $macAddress,
        string $status,
        ?string $reason
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO pppoe_auth_logs (
                router_id, account_id, username, caller_id, mac_address, status, reason, attempted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $routerId,
            $accountId,
            $username,
            $callerId,
            $macAddress,
            $status,
            $reason,
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function getAccountStatistics(int $accountId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT 
                COALESCE(SUM(uptime_seconds), 0) AS total_uptime_seconds,
                COALESCE(SUM(bytes_in), 0) AS total_bytes_in,
                COALESCE(SUM(bytes_out), 0) AS total_bytes_out,
                COUNT(*) AS session_count
            FROM pppoe_session_history
            WHERE account_id = :account_id
        ');
        $stmt->execute(['account_id' => $accountId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_uptime_seconds' => (int) ($row['total_uptime_seconds'] ?? 0),
            'total_bytes_in' => (int) ($row['total_bytes_in'] ?? 0),
            'total_bytes_out' => (int) ($row['total_bytes_out'] ?? 0),
            'session_count' => (int) ($row['session_count'] ?? 0),
        ];
    }
}
