<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Repositories;

use PDO;
use SkyFi\Infrastructure\Contracts\NetworkDeviceRepositoryContract;
use SkyFi\Infrastructure\Data\NetworkDeviceListFilters;
use SkyFi\Infrastructure\Models\NetworkDevice;

final class PdoNetworkDeviceRepository implements NetworkDeviceRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?NetworkDevice
    {
        $stmt = $this->pdo->prepare('
            SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name, mr.name AS mikrotik_router_name
            FROM network_devices nd
            LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
            LEFT JOIN towers t ON nd.tower_id = t.id
            LEFT JOIN mikrotik_routers mr ON nd.mikrotik_router_id = mr.id
            WHERE nd.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? NetworkDevice::fromRow($row) : null;
    }

    public function findActive(int $id): ?NetworkDevice
    {
        $stmt = $this->pdo->prepare('
            SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name, mr.name AS mikrotik_router_name
            FROM network_devices nd
            LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
            LEFT JOIN towers t ON nd.tower_id = t.id
            LEFT JOIN mikrotik_routers mr ON nd.mikrotik_router_id = mr.id
            WHERE nd.id = ? AND nd.deleted_at IS NULL
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? NetworkDevice::fromRow($row) : null;
    }

    public function serialExists(string $serial, ?int $excludeId = null): bool
    {
        if (empty($serial)) {
            return false;
        }

        $sql = 'SELECT 1 FROM network_devices WHERE serial_number = ? AND deleted_at IS NULL';
        $params = [$serial];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function macExists(string $mac, ?int $excludeId = null): bool
    {
        if (empty($mac)) {
            return false;
        }

        $sql = 'SELECT 1 FROM network_devices WHERE mac_address = ? AND deleted_at IS NULL';
        $params = [$mac];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function ipExists(string $ip, ?int $excludeId = null): bool
    {
        if (empty($ip)) {
            return false;
        }

        $sql = 'SELECT 1 FROM network_devices WHERE ip_address = ? AND deleted_at IS NULL';
        $params = [$ip];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function list(NetworkDeviceListFilters $filters): array
    {
        $where = ['nd.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null) {
            $where[] = '(nd.name LIKE ? OR nd.serial_number LIKE ? OR nd.mac_address LIKE ? OR nd.ip_address LIKE ? OR nd.model LIKE ?)';
            $searchTerm = '%' . $filters->search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if ($filters->status !== null) {
            $where[] = 'nd.status = ?';
            $params[] = $filters->status;
        }

        if ($filters->deviceType !== null) {
            $where[] = 'nd.device_type = ?';
            $params[] = $filters->deviceType;
        }

        if ($filters->popSiteId !== null) {
            $where[] = 'nd.pop_site_id = ?';
            $params[] = $filters->popSiteId;
        }

        if ($filters->towerId !== null) {
            $where[] = 'nd.tower_id = ?';
            $params[] = $filters->towerId;
        }

        if ($filters->mikrotikRouterId !== null) {
            $where[] = 'nd.mikrotik_router_id = ?';
            $params[] = $filters->mikrotikRouterId;
        }

        $whereSql = implode(' AND ', $where);

        // Count total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM network_devices nd WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Sorting
        $allowedSorts = ['name', 'device_type', 'vendor', 'model', 'status', 'ip_address', 'created_at', '-name', '-device_type', '-vendor', '-model', '-status', '-ip_address', '-created_at'];
        $sort = in_array($filters->sort, $allowedSorts, true) ? $filters->sort : '-created_at';
        $sortDirection = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
        $sortColumn = ltrim($sort, '-');
        $sortColumn = "nd.{$sortColumn}";

        // Pagination
        $page = max(1, $filters->page);
        $perPage = min(max(1, $filters->perPage), 100);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name, mr.name AS mikrotik_router_name
                FROM network_devices nd
                LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
                LEFT JOIN towers t ON nd.tower_id = t.id
                LEFT JOIN mikrotik_routers mr ON nd.mikrotik_router_id = mr.id
                WHERE {$whereSql}
                ORDER BY {$sortColumn} {$sortDirection}
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $items = array_map(fn($row) => NetworkDevice::fromRow($row), $rows);
        $lastPage = (int) ceil($total / $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function create(array $data): NetworkDevice
    {
        $sql = 'INSERT INTO network_devices (
            pop_site_id, tower_id, name, device_type, vendor, model, serial_number,
            mac_address, ip_address, firmware_version, location_description, management_vlan,
            management_username, management_password_encrypted, status, notes, mikrotik_router_id,
            created_by, updated_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->pdo->prepare($sql)->execute([
            $data['pop_site_id'] ?? null,
            $data['tower_id'] ?? null,
            $data['name'],
            $data['device_type'],
            $data['vendor'] ?? null,
            $data['model'] ?? null,
            $data['serial_number'] ?? null,
            $data['mac_address'] ?? null,
            $data['ip_address'] ?? null,
            $data['firmware_version'] ?? null,
            $data['location_description'] ?? null,
            $data['management_vlan'] ?? null,
            $data['management_username'] ?? null,
            $data['management_password_encrypted'] ?? null,
            $data['status'],
            $data['notes'] ?? null,
            $data['mikrotik_router_id'] ?? null,
            $data['created_by'],
            $data['updated_by'] ?? null,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findActive($id) ?? throw new \RuntimeException('Entity could not be reloaded.');
    }

    public function update(int $id, array $data): NetworkDevice
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

        $sql = 'UPDATE network_devices SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $this->pdo->prepare($sql)->execute($params);

        return $this->findActive($id) ?? throw new \RuntimeException('Entity could not be reloaded.');
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE network_devices SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE network_devices SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function getByPopSite(int $popSiteId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name, mr.name AS mikrotik_router_name
            FROM network_devices nd
            LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
            LEFT JOIN towers t ON nd.tower_id = t.id
            LEFT JOIN mikrotik_routers mr ON nd.mikrotik_router_id = mr.id
            WHERE nd.pop_site_id = ? AND nd.deleted_at IS NULL
            ORDER BY nd.device_type, nd.name
        ');
        $stmt->execute([$popSiteId]);

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => NetworkDevice::fromRow($row), $rows);
    }

    public function getByTower(int $towerId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name, mr.name AS mikrotik_router_name
            FROM network_devices nd
            LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
            LEFT JOIN towers t ON nd.tower_id = t.id
            LEFT JOIN mikrotik_routers mr ON nd.mikrotik_router_id = mr.id
            WHERE nd.tower_id = ? AND nd.deleted_at IS NULL
            ORDER BY nd.device_type, nd.name
        ');
        $stmt->execute([$towerId]);

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => NetworkDevice::fromRow($row), $rows);
    }

    public function getByType(string $type): array
    {
        $stmt = $this->pdo->prepare('
            SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name, mr.name AS mikrotik_router_name
            FROM network_devices nd
            LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
            LEFT JOIN towers t ON nd.tower_id = t.id
            LEFT JOIN mikrotik_routers mr ON nd.mikrotik_router_id = mr.id
            WHERE nd.device_type = ? AND nd.deleted_at IS NULL
            ORDER BY nd.name
        ');
        $stmt->execute([$type]);

        $rows = $stmt->fetchAll();
        return array_map(fn($row) => NetworkDevice::fromRow($row), $rows);
    }

    public function getByMikrotikRouterId(int $mikrotikRouterId): ?NetworkDevice
    {
        $stmt = $this->pdo->prepare('
            SELECT nd.*, ps.name AS pop_site_name, t.name AS tower_name, mr.name AS mikrotik_router_name
            FROM network_devices nd
            LEFT JOIN pop_sites ps ON nd.pop_site_id = ps.id
            LEFT JOIN towers t ON nd.tower_id = t.id
            LEFT JOIN mikrotik_routers mr ON nd.mikrotik_router_id = mr.id
            WHERE nd.mikrotik_router_id = ? AND nd.deleted_at IS NULL
        ');
        $stmt->execute([$mikrotikRouterId]);
        $row = $stmt->fetch();

        return $row ? NetworkDevice::fromRow($row) : null;
    }
}