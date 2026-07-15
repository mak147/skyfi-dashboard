<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Repositories;

use PDO;
use SkyFi\Infrastructure\Contracts\TowerRepositoryContract;
use SkyFi\Infrastructure\Data\TowerListFilters;
use SkyFi\Infrastructure\Models\Tower;

final class PdoTowerRepository implements TowerRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?Tower
    {
        $stmt = $this->pdo->prepare('
            SELECT t.*, ps.name AS pop_site_name
            FROM towers t
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            WHERE t.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Tower::fromRow($row) : null;
    }

    public function findActive(int $id): ?Tower
    {
        $stmt = $this->pdo->prepare('
            SELECT t.*, ps.name AS pop_site_name
            FROM towers t
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            WHERE t.id = ? AND t.deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Tower::fromRow($row) : null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        if (empty($code)) {
            return false;
        }

        $sql = 'SELECT 1 FROM towers WHERE code = ? AND deleted_at IS NULL';
        $params = [$code];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function nameExistsInPopSite(int $popSiteId, string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM towers WHERE pop_site_id = ? AND name = ? AND deleted_at IS NULL';
        $params = [$popSiteId, $name];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function list(TowerListFilters $filters): array
    {
        $where = ['t.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null) {
            $where[] = '(t.name LIKE ? OR t.code LIKE ? OR t.city LIKE ? OR t.region LIKE ?)';
            $searchTerm = '%' . $filters->search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if ($filters->status !== null) {
            $where[] = 't.status = ?';
            $params[] = $filters->status;
        }

        if ($filters->towerType !== null) {
            $where[] = 't.tower_type = ?';
            $params[] = $filters->towerType;
        }

        if ($filters->popSiteId !== null) {
            $where[] = 't.pop_site_id = ?';
            $params[] = $filters->popSiteId;
        }

        if ($filters->city !== null) {
            $where[] = 't.city = ?';
            $params[] = $filters->city;
        }

        if ($filters->region !== null) {
            $where[] = 't.region = ?';
            $params[] = $filters->region;
        }

        $whereSql = implode(' AND ', $where);

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM towers t WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Sorting
        $allowedSorts = ['name', 'code', 'tower_type', 'city', 'status', 'height_meters', 'created_at', '-name', '-code', '-tower_type', '-city', '-status', '-height_meters', '-created_at'];
        $sort = in_array($filters->sort, $allowedSorts, true) ? $filters->sort : '-created_at';
        $sortDirection = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
        $sortColumn = ltrim($sort, '-');
        $sortColumn = $sortColumn === 'tower_type' ? 't.tower_type' : "t.{$sortColumn}";

        // Pagination
        $page = max(1, $filters->page);
        $perPage = min(max(1, $filters->perPage), 100);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT t.*, ps.name AS pop_site_name
                FROM towers t
                LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
                WHERE {$whereSql}
                ORDER BY {$sortColumn} {$sortDirection}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $items = array_map(fn($row) => Tower::fromRow($row), $rows);
        $lastPage = (int) ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function create(array $data): Tower
    {
        $sql = 'INSERT INTO towers (
            pop_site_id, name, code, tower_type, height_meters, owner,
            address_line1, city, region, gps_latitude, gps_longitude,
            status, notes, created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->pdo->prepare($sql)->execute([
            $data['pop_site_id'],
            $data['name'],
            $data['code'] ?? null,
            $data['tower_type'],
            $data['height_meters'] ?? null,
            $data['owner'],
            $data['address_line1'] ?? null,
            $data['city'] ?? null,
            $data['region'] ?? null,
            $data['gps_latitude'] ?? null,
            $data['gps_longitude'] ?? null,
            $data['status'],
            $data['notes'] ?? null,
            $data['created_by'],
            $data['updated_by'] ?? null,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findActive($id) ?? throw new \RuntimeException('Entity could not be reloaded.');
    }

    public function update(int $id, array $data): Tower
    {
        if (empty($data)) {
            return $this->findActive($id) ?? throw new \RuntimeException('Entity could not be reloaded.');
        }

        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = 'UPDATE towers SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $this->pdo->prepare($sql)->execute($params);

        return $this->findActive($id) ?? throw new \RuntimeException('Entity could not be reloaded.');
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE towers SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE towers SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function getSectorsForTower(int $towerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, nd.name AS device_name
            FROM sectors s
            LEFT JOIN network_devices nd ON s.device_id = nd.id
            WHERE s.tower_id = ? AND s.deleted_at IS NULL
            ORDER BY s.azimuth
        ');
        $stmt->execute([$towerId]);

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => \SkyFi\Infrastructure\Models\Sector::fromRow($row), $rows);
    }

    public function getDevicesForTower(int $towerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name
            FROM network_devices nd
            LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
            LEFT JOIN towers t ON nd.tower_id = t.id
            WHERE nd.tower_id = ? AND nd.deleted_at IS NULL
            ORDER BY nd.device_type, nd.name
        ');
        $stmt->execute([$towerId]);

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => \SkyFi\Infrastructure\Models\NetworkDevice::fromRow($row), $rows);
    }

    public function getMapPoints(): array
    {
        $stmt = $this->pdo->query('
            SELECT t.id, t.name, t.code, t.gps_latitude, t.gps_longitude, t.status, t.tower_type, t.height_meters,
                   ps.name AS pop_site_name, ps.id AS pop_site_id
            FROM towers t
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            WHERE t.deleted_at IS NULL AND t.gps_latitude IS NOT NULL AND t.gps_longitude IS NOT NULL
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByPopSite(int $popSiteId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT t.*, ps.name AS pop_site_name
            FROM towers t
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            WHERE t.pop_site_id = ? AND t.deleted_at IS NULL
            ORDER BY t.name
        ');
        $stmt->execute([$popSiteId]);

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => Tower::fromRow($row), $rows);
    }
}