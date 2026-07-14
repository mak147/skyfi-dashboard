<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Repositories;

use PDO;
use SkyFi\Pppoe\Contracts\PppoeAccountRepositoryContract;
use SkyFi\Pppoe\DomainModels\PppoeAccount;
use SkyFi\Pppoe\DTOs\PppoeListFilters;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoPppoeAccountRepository implements PppoeAccountRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(PppoeListFilters $filters): array
    {
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if ($filters->status !== null) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->syncStatus !== null) {
            $where[] = 'p.sync_status = :sync_status';
            $params['sync_status'] = $filters->syncStatus;
        }

        if ($filters->customerId !== null) {
            $where[] = 'p.customer_id = :customer_id';
            $params['customer_id'] = $filters->customerId;
        }

        if ($filters->connectionId !== null) {
            $where[] = 'p.connection_id = :connection_id';
            $params['connection_id'] = $filters->connectionId;
        }

        if ($filters->packageId !== null) {
            $where[] = 'p.package_id = :package_id';
            $params['package_id'] = $filters->packageId;
        }

        if ($filters->routerId !== null) {
            $where[] = 'p.router_id = :router_id';
            $params['router_id'] = $filters->routerId;
        }

        if ($filters->search !== null) {
            $where[] = '(p.username LIKE :search OR p.profile LIKE :search OR p.static_ip LIKE :search OR p.caller_id LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM pppoe_accounts p WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        $offset = ($filters->page - 1) * $filters->perPage;

        $sortField = ltrim($filters->sort, '-');
        $sortOrder = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';

        $allowedSorts = ['id', 'username', 'profile', 'status', 'sync_status', 'created_at', 'last_connected_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
            $sortOrder = 'DESC';
        }

        $selectSql = "SELECT p.* FROM pppoe_accounts p WHERE {$whereSql} ORDER BY p.{$sortField} {$sortOrder} LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($selectSql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = PppoeAccount::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function find(int $id): ?PppoeAccount
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pppoe_accounts WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? PppoeAccount::fromRow($row) : null;
    }

    public function findByUsername(string $username): ?PppoeAccount
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pppoe_accounts WHERE username = :username AND deleted_at IS NULL');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? PppoeAccount::fromRow($row) : null;
    }

    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM pppoe_accounts WHERE username = :username AND deleted_at IS NULL';
        $params = ['username' => $username];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function listByRouter(int $routerId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pppoe_accounts WHERE router_id = :router_id AND deleted_at IS NULL ORDER BY username ASC');
        $stmt->execute(['router_id' => $routerId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = PppoeAccount::fromRow($row);
        }

        return $items;
    }

    public function listByCustomer(int $customerId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pppoe_accounts WHERE customer_id = :customer_id AND deleted_at IS NULL ORDER BY created_at DESC');
        $stmt->execute(['customer_id' => $customerId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = PppoeAccount::fromRow($row);
        }

        return $items;
    }

    public function listByConnection(int $connectionId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pppoe_accounts WHERE connection_id = :connection_id AND deleted_at IS NULL ORDER BY created_at DESC');
        $stmt->execute(['connection_id' => $connectionId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = PppoeAccount::fromRow($row);
        }

        return $items;
    }

    public function insert(array $data): PppoeAccount
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn ($k): string => ":{$k}", array_keys($data)));

        $sql = "INSERT INTO pppoe_accounts ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        $inserted = $this->find((int) $this->pdo->lastInsertId());
        if ($inserted === null) {
            throw new NotFoundException('Failed to retrieve inserted PPPoE account.');
        }

        return $inserted;
    }

    public function update(int $id, array $data): PppoeAccount
    {
        if ($data === []) {
            $existing = $this->find($id);
            return $existing ?? throw new NotFoundException('PPPoE account not found.');
        }

        $sets = implode(', ', array_map(static fn ($k): string => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE pppoe_accounts SET {$sets} WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$data, 'id' => $id]);

        $updated = $this->find($id);
        if ($updated === null) {
            throw new NotFoundException('PPPoE account not found.');
        }

        return $updated;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE pppoe_accounts SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updateSyncStatus(int $id, string $syncStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE pppoe_accounts SET sync_status = :sync_status, last_synced_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id, 'sync_status' => $syncStatus]);
    }
}
