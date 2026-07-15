<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Repositories;

use PDO;
use SkyFi\Infrastructure\Contracts\PopSiteRepositoryContract;
use SkyFi\Infrastructure\Data\PopSiteListFilters;
use SkyFi\Infrastructure\Models\PopSite;

final class PdoPopSiteRepository implements PopSiteRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?PopSite
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pop_sites WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? PopSite::fromRow($row) : null;
    }

    public function findActive(int $id): ?PopSite
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pop_sites WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? PopSite::fromRow($row) : null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM pop_sites WHERE code = ? AND deleted_at IS NULL';
        $params = [$code];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM pop_sites WHERE name = ? AND deleted_at IS NULL';
        $params = [$name];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function list(PopSiteListFilters $filters): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null) {
            $where[] = '(name LIKE ? OR code LIKE ? OR city LIKE ? OR region LIKE ?)';
            $searchTerm = '%' . $filters->search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if ($filters->status !== null) {
            $where[] = 'status = ?';
            $params[] = $filters->status;
        }

        if ($filters->city !== null) {
            $where[] = 'city = ?';
            $params[] = $filters->city;
        }

        if ($filters->region !== null) {
            $where[] = 'region = ?';
            $params[] = $filters->region;
        }

        if ($filters->powerStatus !== null) {
            $where[] = 'power_status = ?';
            $params[] = $filters->powerStatus;
        }

        $whereSql = implode(' AND ', $where);

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM pop_sites WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Sorting
        $allowedSorts = ['name', 'code', 'city', 'status', 'created_at', '-name', '-code', '-city', '-status', '-created_at'];
        $sort = in_array($filters->sort, $allowedSorts, true) ? $filters->sort : '-created_at';
        $sortDirection = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
        $sortColumn = ltrim($sort, '-');

        // Pagination
        $page = max(1, $filters->page);
        $perPage = min(max(1, $filters->perPage), 100);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT ps.*, u.name AS created_by_name
                FROM pop_sites ps
                LEFT JOIN users u ON ps.created_by = u.id
                WHERE {$whereSql}
                ORDER BY {$sortColumn} {$sortDirection}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $items = array_map(fn($row) => PopSite::fromRow($row), $rows);
        $lastPage = (int) ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function create(array $data): PopSite
    {
        $sql = 'INSERT INTO pop_sites (
            name, code, address_line1, address_line2, city, region, country,
            gps_latitude, gps_longitude, contact_person, contact_phone, contact_email,
            power_status, fiber_provider, status, notes, created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->pdo->prepare($sql)->execute([
            $data['name'],
            $data['code'],
            $data['address_line1'] ?? null,
            $data['address_line2'] ?? null,
            $data['city'] ?? null,
            $data['region'] ?? null,
            $data['country'] ?? 'Pakistan',
            $data['gps_latitude'] ?? null,
            $data['gps_longitude'] ?? null,
            $data['contact_person'] ?? null,
            $data['contact_phone'] ?? null,
            $data['contact_email'] ?? null,
            $data['power_status'],
            $data['fiber_provider'] ?? null,
            $data['status'],
            $data['notes'] ?? null,
            $data['created_by'],
            $data['updated_by'] ?? null,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findActive($id) ?? throw new \RuntimeException('Entity could not be reloaded.');
    }

    public function update(int $id, array $data): PopSite
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

        $sql = 'UPDATE pop_sites SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $this->pdo->prepare($sql)->execute($params);

        return $this->findActive($id) ?? throw new \RuntimeException('Entity could not be reloaded.');
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE pop_sites SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE pop_sites SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function getTowersForPopSite(int $popSiteId): array
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
        return array_map(fn($row) => \SkyFi\Infrastructure\Models\Tower::fromRow($row), $rows);
    }

    public function getMapPoints(): array
    {
        $stmt = $this->pdo->query('
            SELECT id, name, code, gps_latitude, gps_longitude, status, city, region
            FROM pop_sites
            WHERE deleted_at IS NULL AND gps_latitude IS NOT NULL AND gps_longitude IS NOT NULL
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}