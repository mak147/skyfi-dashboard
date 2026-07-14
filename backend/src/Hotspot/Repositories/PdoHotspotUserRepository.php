<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Repositories;

use PDO;
use SkyFi\Hotspot\Contracts\HotspotUserRepositoryContract;
use SkyFi\Hotspot\DomainModels\HotspotUser;
use SkyFi\Hotspot\DTOs\HotspotUserListFilters;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoHotspotUserRepository implements HotspotUserRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(HotspotUserListFilters $filters): array
    {
        $where = ['h.deleted_at IS NULL'];
        $params = [];

        if ($filters->status !== null) {
            $where[] = 'h.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->syncStatus !== null) {
            $where[] = 'h.sync_status = :sync_status';
            $params['sync_status'] = $filters->syncStatus;
        }

        if ($filters->customerId !== null) {
            $where[] = 'h.customer_id = :customer_id';
            $params['customer_id'] = $filters->customerId;
        }

        if ($filters->routerId !== null) {
            $where[] = 'h.router_id = :router_id';
            $params['router_id'] = $filters->routerId;
        }

        if ($filters->profileId !== null) {
            $where[] = 'h.profile_id = :profile_id';
            $params['profile_id'] = $filters->profileId;
        }

        if ($filters->packageId !== null) {
            $where[] = 'h.package_id = :package_id';
            $params['package_id'] = $filters->packageId;
        }

        if ($filters->search !== null) {
            $where[] = '(h.username LIKE :search OR h.profile_name LIKE :search OR h.mac_address LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM hotspot_users h WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        $offset = ($filters->page - 1) * $filters->perPage;

        $sortField = ltrim($filters->sort, '-');
        $sortOrder = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';

        $allowedSorts = ['id', 'username', 'profile_name', 'status', 'sync_status', 'created_at', 'last_connected_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
            $sortOrder = 'DESC';
        }

        $selectSql = "SELECT h.* FROM hotspot_users h WHERE {$whereSql} ORDER BY h.{$sortField} {$sortOrder} LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($selectSql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = HotspotUser::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function find(int $id): ?HotspotUser
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_users WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? HotspotUser::fromRow($row) : null;
    }

    public function findByUsername(string $username): ?HotspotUser
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_users WHERE username = :username AND deleted_at IS NULL');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? HotspotUser::fromRow($row) : null;
    }

    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM hotspot_users WHERE username = :username AND deleted_at IS NULL';
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
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_users WHERE router_id = :router_id AND deleted_at IS NULL ORDER BY username ASC');
        $stmt->execute(['router_id' => $routerId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = HotspotUser::fromRow($row);
        }

        return $items;
    }

    public function insert(array $data): HotspotUser
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn ($k): string => ":{$k}", array_keys($data)));

        $sql = "INSERT INTO hotspot_users ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        $inserted = $this->find((int) $this->pdo->lastInsertId());
        if ($inserted === null) {
            throw new NotFoundException('Failed to retrieve inserted hotspot user.');
        }

        return $inserted;
    }

    public function update(int $id, array $data): HotspotUser
    {
        if ($data === []) {
            $existing = $this->find($id);
            return $existing ?? throw new NotFoundException('Hotspot user not found.');
        }

        $sets = implode(', ', array_map(static fn ($k): string => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE hotspot_users SET {$sets} WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$data, 'id' => $id]);

        $updated = $this->find($id);
        if ($updated === null) {
            throw new NotFoundException('Hotspot user not found.');
        }

        return $updated;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE hotspot_users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updateSyncStatus(int $id, string $syncStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE hotspot_users SET sync_status = :sync_status, last_synced_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id, 'sync_status' => $syncStatus]);
    }

    public function countByStatus(?string $status = null): int
    {
        if ($status !== null) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM hotspot_users WHERE status = :status AND deleted_at IS NULL");
            $stmt->execute(['status' => $status]);
        } else {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM hotspot_users WHERE deleted_at IS NULL");
        }
        return (int) $stmt->fetchColumn();
    }

    public function countBySyncStatus(string $syncStatus): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM hotspot_users WHERE sync_status = :sync_status AND deleted_at IS NULL");
        $stmt->execute(['sync_status' => $syncStatus]);
        return (int) $stmt->fetchColumn();
    }
}
