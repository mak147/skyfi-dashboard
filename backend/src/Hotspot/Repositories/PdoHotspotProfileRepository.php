<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Repositories;

use PDO;
use SkyFi\Hotspot\Contracts\HotspotProfileRepositoryContract;
use SkyFi\Hotspot\DomainModels\HotspotProfile;
use SkyFi\Hotspot\DTOs\HotspotProfileListFilters;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoHotspotProfileRepository implements HotspotProfileRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(HotspotProfileListFilters $filters): array
    {
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        if ($filters->status !== null) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->routerId !== null) {
            $where[] = 'p.router_id = :router_id';
            $params['router_id'] = $filters->routerId;
        }

        if ($filters->search !== null) {
            $where[] = '(p.name LIKE :search OR p.router_profile_name LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM hotspot_profiles p WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        $offset = ($filters->page - 1) * $filters->perPage;

        $sortField = ltrim($filters->sort, '-');
        $sortOrder = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';

        $allowedSorts = ['id', 'name', 'router_profile_name', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
            $sortOrder = 'DESC';
        }

        $selectSql = "SELECT p.* FROM hotspot_profiles p WHERE {$whereSql} ORDER BY p.{$sortField} {$sortOrder} LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($selectSql);
        $stmt->execute($params);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = HotspotProfile::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function find(int $id): ?HotspotProfile
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_profiles WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? HotspotProfile::fromRow($row) : null;
    }

    public function findByRouterAndName(int $routerId, string $routerProfileName): ?HotspotProfile
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_profiles WHERE router_id = :router_id AND router_profile_name = :name AND deleted_at IS NULL');
        $stmt->execute(['router_id' => $routerId, 'name' => $routerProfileName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? HotspotProfile::fromRow($row) : null;
    }

    public function listByRouter(int $routerId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotspot_profiles WHERE router_id = :router_id AND deleted_at IS NULL ORDER BY name ASC');
        $stmt->execute(['router_id' => $routerId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = HotspotProfile::fromRow($row);
        }

        return $items;
    }

    public function insert(array $data): HotspotProfile
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn ($k): string => ":{$k}", array_keys($data)));

        $sql = "INSERT INTO hotspot_profiles ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        $inserted = $this->find((int) $this->pdo->lastInsertId());
        if ($inserted === null) {
            throw new NotFoundException('Failed to retrieve inserted hotspot profile.');
        }

        return $inserted;
    }

    public function update(int $id, array $data): HotspotProfile
    {
        if ($data === []) {
            $existing = $this->find($id);
            return $existing ?? throw new NotFoundException('Hotspot profile not found.');
        }

        $sets = implode(', ', array_map(static fn ($k): string => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE hotspot_profiles SET {$sets} WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...$data, 'id' => $id]);

        $updated = $this->find($id);
        if ($updated === null) {
            throw new NotFoundException('Hotspot profile not found.');
        }

        return $updated;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE hotspot_profiles SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updateSyncStatus(int $id, string $syncStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE hotspot_profiles SET sync_status = :sync_status, last_synced_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id, 'sync_status' => $syncStatus]);
    }
}
