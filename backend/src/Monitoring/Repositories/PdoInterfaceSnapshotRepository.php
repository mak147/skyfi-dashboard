<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Repositories;

use PDO;
use SkyFi\Monitoring\Contracts\InterfaceSnapshotRepositoryContract;
use SkyFi\Monitoring\DomainModels\InterfaceSnapshot;
use SkyFi\Monitoring\DTOs\InterfaceMetricsFilters;

final class PdoInterfaceSnapshotRepository implements InterfaceSnapshotRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $data */
    public function recordSnapshot(array $data): InterfaceSnapshot
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO monitoring_interface_snapshots (
                router_id, interface_name, interface_type, running, disabled, mtu,
                rx_bytes, tx_bytes, rx_bps, tx_bps, link_status, checked_at
            ) VALUES (
                :router_id, :interface_name, :interface_type, :running, :disabled, :mtu,
                :rx_bytes, :tx_bytes, :rx_bps, :tx_bps, :link_status, :checked_at
            )'
        );
        $checkedAt = (string) ($data['checked_at'] ?? gmdate('Y-m-d H:i:s'));
        $stmt->execute([
            'router_id' => (int) $data['router_id'],
            'interface_name' => (string) $data['interface_name'],
            'interface_type' => isset($data['interface_type']) ? (string) $data['interface_type'] : null,
            'running' => !empty($data['running']) ? 1 : 0,
            'disabled' => !empty($data['disabled']) ? 1 : 0,
            'mtu' => isset($data['mtu']) && $data['mtu'] !== null ? (int) $data['mtu'] : null,
            'rx_bytes' => (int) ($data['rx_bytes'] ?? 0),
            'tx_bytes' => (int) ($data['tx_bytes'] ?? 0),
            'rx_bps' => (int) ($data['rx_bps'] ?? 0),
            'tx_bps' => (int) ($data['tx_bps'] ?? 0),
            'link_status' => (string) ($data['link_status'] ?? 'down'),
            'checked_at' => $checkedAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? throw new \RuntimeException('Failed to retrieve interface snapshot.');
    }

    public function getLatestSnapshotForInterface(int $routerId, string $interfaceName): ?InterfaceSnapshot
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM monitoring_interface_snapshots WHERE router_id = :router_id AND interface_name = :interface_name ORDER BY checked_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['router_id' => $routerId, 'interface_name' => $interfaceName]);
        $row = $stmt->fetch();

        return $row === false ? null : InterfaceSnapshot::fromRow($row);
    }

    /** @return array{items: array<int, InterfaceSnapshot>, total: int, page: int, per_page: int} */
    public function listSnapshots(InterfaceMetricsFilters $filters): array
    {
        $conditions = [];
        $params = [];

        if ($filters->routerId !== null) {
            $conditions[] = 'router_id = :router_id';
            $params['router_id'] = $filters->routerId;
        }
        if ($filters->linkStatus !== null) {
            $conditions[] = 'link_status = :link_status';
            $params['link_status'] = $filters->linkStatus;
        }

        $whereClause = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM monitoring_interface_snapshots {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $stmt = $this->pdo->prepare(
            "SELECT * FROM monitoring_interface_snapshots {$whereClause} ORDER BY checked_at DESC, id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = InterfaceSnapshot::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'per_page' => $filters->perPage,
        ];
    }

    /** @return array<int, InterfaceSnapshot> */
    public function getLatestSnapshotsForRouter(int $routerId): array
    {
        // Get subquery of latest id per interface_name for router
        $stmt = $this->pdo->prepare(
            'SELECT s.* FROM monitoring_interface_snapshots s
             INNER JOIN (
                 SELECT interface_name, MAX(id) AS max_id FROM monitoring_interface_snapshots WHERE router_id = :router_id GROUP BY interface_name
             ) latest ON s.id = latest.max_id
             ORDER BY s.interface_name ASC'
        );
        $stmt->execute(['router_id' => $routerId]);

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = InterfaceSnapshot::fromRow($row);
        }

        return $items;
    }

    private function find(int $id): ?InterfaceSnapshot
    {
        $stmt = $this->pdo->prepare('SELECT * FROM monitoring_interface_snapshots WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : InterfaceSnapshot::fromRow($row);
    }
}
