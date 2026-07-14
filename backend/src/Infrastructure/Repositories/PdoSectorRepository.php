<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Repositories;

use PDO;
use SkyFi\Infrastructure\Contracts\SectorRepositoryContract;
use SkyFi\Infrastructure\Data\SectorListFilters;
use SkyFi\Infrastructure\Models\Sector;

final class PdoSectorRepository implements SectorRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?Sector
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, t.name AS tower_name, ps.name AS pop_site_name, nd.name AS device_name
            FROM sectors s
            LEFT JOIN towers t ON s.tower_id = t.id
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            LEFT JOIN network_devices nd ON s.device_id = nd.id
            WHERE s.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Sector::fromRow($row) : null;
    }

    public function findActive(int $id): ?Sector
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, t.name AS tower_name, ps.name AS pop_site_name, nd.name AS device_name
            FROM sectors s
            LEFT JOIN towers t ON s.tower_id = t.id
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            LEFT JOIN network_devices nd ON s.device_id = nd.id
            WHERE s.id = ? AND s.deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Sector::fromRow($row) : null;
    }

    public function list(SectorListFilters $filters): array
    {
        $where = ['s.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null) {
            $where[] = '(s.name LIKE ? OR s.ssid LIKE ?)';
            $searchTerm = '%' . $filters->search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }

        if ($filters->status !== null) {
            $where[] = 's.status = ?';
            $params[] = $filters->status;
        }

        if ($filters->towerId !== null) {
            $where[] = 's.tower_id = ?';
            $params[] = $filters->towerId;
        }

        if ($filters->deviceId !== null) {
            $where[] = 's.device_id = ?';
            $params[] = $filters->deviceId;
        }

        if ($filters->frequencyMhz !== null) {
            $where[] = 's.frequency_mhz = ?';
            $params[] = $filters->frequencyMhz;
        }

        $whereSql = implode(' AND ', $where);

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM sectors s WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Sorting
        $allowedSorts = ['name', 'azimuth', 'frequency_mhz', 'status', 'created_at', '-name', '-azimuth', '-frequency_mhz', '-status', '-created_at'];
        $sort = in_array($filters->sort, $allowedSorts, true) ? $filters->sort : '-created_at';
        $sortDirection = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
        $sortColumn = ltrim($sort, '-');
        $sortColumn = "s.{$sortColumn}";

        // Pagination
        $page = max(1, $filters->page);
        $perPage = min(max(1, $filters->perPage), 100);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT s.*, t.name AS tower_name, ps.name AS pop_site_name, nd.name AS device_name
                FROM sectors s
                LEFT JOIN towers t ON s.tower_id = t.id
                LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
                LEFT JOIN network_devices nd ON s.device_id = nd.id
                WHERE {$whereSql}
                ORDER BY {$sortColumn} {$sortDirection}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $items = array_map(fn($row) => Sector::fromRow($row), $rows);
        $lastPage = (int) ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function create(array $data): Sector
    {
        $sql = 'INSERT INTO sectors (
            tower_id, name, azimuth, beamwidth, frequency_mhz, channel_width_mhz,
            ssid, eirp_dbm, device_id, capacity_mbps, max_subscribers, status, notes, created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->pdo->prepare($sql)->execute([
            $data['tower_id'],
            $data['name'],
            $data['azimuth'],
            $data['beamwidth'] ?? null,
            $data['frequency_mhz'],
            $data['channel_width_mhz'] ?? null,
            $data['ssid'] ?? null,
            $data['eirp_dbm'] ?? null,
            $data['device_id'] ?? null,
            $data['capacity_mbps'] ?? null,
            $data['max_subscribers'] ?? null,
            $data['status'],
            $data['notes'] ?? null,
            $data['created_by'],
            $data['updated_by'] ?? null,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findActive($id)!;
    }

    public function update(int $id, array $data): Sector
    {
        if (empty($data)) {
            return $this->findActive($id)!;
        }

        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = 'UPDATE sectors SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $this->pdo->prepare($sql)->execute($params);

        return $this->findActive($id)!;
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE sectors SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE sectors SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function getByTower(int $towerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, t.name AS tower_name, ps.name AS pop_site_name, nd.name AS device_name
            FROM sectors s
            LEFT JOIN towers t ON s.tower_id = t.id
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            LEFT JOIN network_devices nd ON s.device_id = nd.id
            WHERE s.tower_id = ? AND s.deleted_at IS NULL
            ORDER BY s.azimuth
        ');
        $stmt->execute([$towerId]);

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => Sector::fromRow($row), $rows);
    }

    public function getWithConnectionCount(int $id): ?Sector
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, t.name AS tower_name, ps.name AS pop_site_name, nd.name AS device_name,
                   COUNT(c.id) AS connection_count
            FROM sectors s
            LEFT JOIN towers t ON s.tower_id = t.id
            LEFT JOIN pop_sites ps ON t.pop_site_id = ps.id
            LEFT JOIN network_devices nd ON s.device_id = nd.id
            LEFT JOIN connections c ON c.sector_id = s.id AND c.deleted_at IS NULL
            WHERE s.id = ? AND s.deleted_at IS NULL
            GROUP BY s.id
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Sector::fromRow($row) : null;
    }

    public function getCoverageData(): array
    {
        $stmt = $this->pdo->query('
            SELECT s.id, s.name, s.azimuth, s.beamwidth, s.frequency_mhz, s.channel_width_mhz,
                   s.eirp_dbm, s.status, s.tower_id,
                   t.name AS tower_name, t.gps_latitude, t.gps_longitude, t.height_meters,
                   ps.name AS pop_site_name
            FROM sectors s
            JOIN towers t ON s.tower_id = t.id
            JOIN pop_sites ps ON t.pop_site_id = ps.id
            WHERE s.deleted_at IS NULL AND s.status = \'active\'
              AND t.deleted_at IS NULL AND t.status = \'active\'
              AND ps.deleted_at IS NULL AND ps.status = \'active\'
              AND t.gps_latitude IS NOT NULL AND t.gps_longitude IS NOT NULL
        ');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}