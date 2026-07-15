<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Repositories;

use PDO;
use SkyFi\Inventory\Contracts\AssetRepositoryContract;
use SkyFi\Inventory\DomainModels\InventoryAsset;
use SkyFi\Inventory\DTOs\AssetAssignmentData;
use SkyFi\Inventory\DTOs\AssetData;
use SkyFi\Inventory\DTOs\AssetListFilters;

final class PdoAssetRepository implements AssetRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(AssetListFilters $filters): array
    {
        [$where, $parameters] = $this->filters($filters);
        $from = $this->fromSql();
        $count = $this->pdo->prepare("SELECT COUNT(DISTINCT a.id) {$from} WHERE {$where}");
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $sorts = ['asset_tag' => 'a.asset_tag', 'serial_number' => 'a.serial_number', 'status' => 'a.status', 'purchase_date' => 'a.purchase_date', 'warranty_expires_at' => 'a.warranty_expires_at', 'created_at' => 'a.created_at'];
        $descending = str_starts_with($filters->sort, '-');
        $order = ($sorts[ltrim($filters->sort, '-')] ?? 'a.created_at') . ($descending ? ' DESC' : ' ASC');
        $offset = ($filters->page - 1) * $filters->perPage;
        $statement = $this->pdo->prepare("SELECT {$this->columns()} {$from} WHERE {$where} ORDER BY {$order} LIMIT {$filters->perPage} OFFSET {$offset}");
        $statement->execute($parameters);
        $items = array_map(static fn(array $row): InventoryAsset => InventoryAsset::fromRow($row), $statement->fetchAll(PDO::FETCH_ASSOC));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => max(1, (int) ceil($total / $filters->perPage))];
    }

    public function find(int $id, bool $forUpdate = false): ?InventoryAsset
    {
        $statement = $this->pdo->prepare("SELECT {$this->columns()} {$this->fromSql()} WHERE a.id = ? AND a.deleted_at IS NULL" . ($forUpdate ? ' FOR UPDATE' : ''));
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row ? InventoryAsset::fromRow($row) : null;
    }

    public function create(AssetData $data, int $actorId): InventoryAsset
    {
        $values = $data->toArray();
        $columns = array_keys($values);
        $this->pdo->prepare('INSERT INTO inventory_assets (' . implode(', ', $columns) . ', created_by) VALUES (' . implode(', ', array_fill(0, count($columns) + 1, '?')) . ')')->execute([...array_values($values), $actorId]);
        $id = (int) $this->pdo->lastInsertId();
        $this->event($id, 'created', 'Asset was registered.', null, $data->status, null, $actorId, ['asset_tag' => $data->assetTag]);
        return $this->find($id) ?? throw new \RuntimeException('Unable to load created asset.');
    }

    public function update(int $id, AssetData $data, int $actorId): InventoryAsset
    {
        $old = $this->find($id, true) ?? throw new \RuntimeException('Asset not found.');
        $values = $data->toArray();
        $sets = array_map(static fn(string $field): string => $field . ' = ?', array_keys($values));
        $this->pdo->prepare('UPDATE inventory_assets SET ' . implode(', ', $sets) . ', updated_by = ? WHERE id = ? AND deleted_at IS NULL')->execute([...array_values($values), $actorId, $id]);
        $this->event($id, 'updated', 'Asset details were updated.', $old->status(), $data->status, null, $actorId);
        return $this->find($id) ?? throw new \RuntimeException('Unable to load updated asset.');
    }

    public function softDelete(int $id, int $actorId): void
    {
        $this->pdo->prepare("UPDATE inventory_assets SET status = 'retired', updated_by = ?, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND deleted_at IS NULL")->execute([$actorId, $id]);
        $this->event($id, 'retired', 'Asset was retired and archived.', null, 'retired', null, $actorId);
    }

    public function assign(int $id, AssetAssignmentData $data, int $actorId, ?string $status = null): InventoryAsset
    {
        $asset = $this->find($id, true) ?? throw new \RuntimeException('Asset not found.');
        $this->pdo->prepare('UPDATE inventory_asset_assignments SET released_at = UTC_TIMESTAMP(), released_by = ? WHERE asset_id = ? AND released_at IS NULL')->execute([$actorId, $id]);
        $values = $data->toArray();
        $this->pdo->prepare('INSERT INTO inventory_asset_assignments (asset_id, assignment_type, warehouse_location_id, customer_id, tower_id, pop_site_id, technician_id, assigned_by, assigned_at, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?)')->execute([
            $id, $values['assignment_type'], $values['warehouse_location_id'], $values['customer_id'], $values['tower_id'], $values['pop_site_id'], $values['technician_id'], $actorId, $values['notes'],
        ]);
        $assignmentId = (int) $this->pdo->lastInsertId();
        $nextStatus = $status ?? ($data->assignmentType === 'warehouse' ? 'in_stock' : ($data->assignmentType === 'technician' ? 'assigned' : 'deployed'));
        $this->pdo->prepare('UPDATE inventory_assets SET status = ?, updated_by = ? WHERE id = ?')->execute([$nextStatus, $actorId, $id]);
        $this->event($id, 'assigned', 'Asset assigned to ' . str_replace('_', ' ', $data->assignmentType) . '.', $asset->status(), $nextStatus, $assignmentId, $actorId, $values);
        return $this->find($id) ?? throw new \RuntimeException('Unable to load assigned asset.');
    }

    public function changeStatus(int $id, string $status, int $actorId, ?string $description = null): InventoryAsset
    {
        $asset = $this->find($id, true) ?? throw new \RuntimeException('Asset not found.');
        $this->pdo->prepare('UPDATE inventory_assets SET status = ?, updated_by = ? WHERE id = ?')->execute([$status, $actorId, $id]);
        $this->event($id, 'status_changed', $description ?? 'Asset status changed.', $asset->status(), $status, null, $actorId);
        return $this->find($id) ?? throw new \RuntimeException('Unable to load updated asset.');
    }

    public function timeline(int $id): array
    {
        $events = $this->pdo->prepare("SELECT e.id, e.event_type AS type, e.description, e.old_status, e.new_status, e.metadata, e.occurred_at, u.name AS actor_name,
            aa.assignment_type, aa.warehouse_location_id, aa.customer_id, aa.tower_id, aa.pop_site_id, aa.technician_id
            FROM inventory_asset_events e LEFT JOIN users u ON u.id = e.actor_user_id LEFT JOIN inventory_asset_assignments aa ON aa.id = e.assignment_id WHERE e.asset_id = ?");
        $events->execute([$id]);
        $items = $events->fetchAll(PDO::FETCH_ASSOC);
        $movements = $this->pdo->prepare("SELECT CONCAT('movement-', m.id) AS id, m.movement_type AS type, CONCAT('Stock movement ', m.movement_number) AS description,
            NULL AS old_status, NULL AS new_status, JSON_OBJECT('movement_id', m.id, 'movement_number', m.movement_number, 'quantity', l.quantity, 'unit_cost', l.unit_cost) AS metadata,
            m.occurred_at, u.name AS actor_name, NULL AS assignment_type, NULL AS warehouse_location_id, NULL AS customer_id, NULL AS tower_id, NULL AS pop_site_id, NULL AS technician_id
            FROM inventory_stock_movement_lines l JOIN inventory_stock_movements m ON m.id = l.movement_id LEFT JOIN users u ON u.id = m.posted_by WHERE l.asset_id = ?");
        $movements->execute([$id]);
        $items = [...$items, ...$movements->fetchAll(PDO::FETCH_ASSOC)];
        usort($items, static fn(array $a, array $b): int => strcmp((string) $b['occurred_at'], (string) $a['occurred_at']));
        foreach ($items as &$item) {
            if (is_string($item['metadata'] ?? null)) {
                $item['metadata'] = json_decode($item['metadata'], true) ?: [];
            }
        }
        return $items;
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    /** @return array{0: string, 1: array<int, mixed>} */
    private function filters(AssetListFilters $filters): array
    {
        $where = ['a.deleted_at IS NULL'];
        $parameters = [];
        if ($filters->search !== null) {
            $where[] = '(a.asset_tag LIKE ? OR a.serial_number LIKE ? OR a.mac_address LIKE ? OR a.imei LIKE ? OR a.barcode LIKE ? OR p.sku LIKE ? OR p.name LIKE ?)';
            $like = '%' . $filters->search . '%';
            array_push($parameters, $like, $like, $like, $like, $like, $like, $like);
        }
        foreach ([['a.status', $filters->status], ['aa.assignment_type', $filters->assignmentType]] as [$column, $value]) {
            if ($value !== null) {
                $where[] = $column . ' = ?';
                $parameters[] = $value;
            }
        }
        foreach ([['a.product_id', $filters->productId], ['p.category_id', $filters->categoryId], ['aa.customer_id', $filters->customerId], ['aa.tower_id', $filters->towerId], ['aa.pop_site_id', $filters->popSiteId], ['aa.technician_id', $filters->technicianId], ['wl.warehouse_id', $filters->warehouseId]] as [$column, $value]) {
            if ($value !== null) {
                $where[] = $column . ' = ?';
                $parameters[] = $value;
            }
        }
        if ($filters->warranty === 'expired') {
            $where[] = 'a.warranty_expires_at < CURRENT_DATE';
        } elseif ($filters->warranty === 'expiring') {
            $where[] = 'a.warranty_expires_at BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)';
        } elseif ($filters->warranty === 'active') {
            $where[] = 'a.warranty_expires_at >= CURRENT_DATE';
        }
        return [implode(' AND ', $where), $parameters];
    }

    private function columns(): string
    {
        return "a.*, p.sku, p.name AS product_name, p.tracking_mode, c.name AS category_name, m.name AS model_name, b.name AS brand_name, v.name AS vendor_name,
            nd.name AS network_device_name, aa.id AS current_assignment_id, aa.assignment_type, aa.assigned_at,
            aa.warehouse_location_id, wl.name AS warehouse_location_name, w.id AS warehouse_id, w.name AS warehouse_name,
            aa.customer_id, cu.full_name AS customer_name, aa.tower_id, t.name AS tower_name, aa.pop_site_id, ps.name AS pop_site_name,
            aa.technician_id, tech.name AS technician_name";
    }

    private function fromSql(): string
    {
        return "FROM inventory_assets a JOIN inventory_products p ON p.id = a.product_id JOIN inventory_categories c ON c.id = p.category_id
            LEFT JOIN inventory_product_models m ON m.id = p.model_id LEFT JOIN inventory_brands b ON b.id = m.brand_id
            LEFT JOIN vendors v ON v.id = a.vendor_id LEFT JOIN network_devices nd ON nd.id = a.network_device_id
            LEFT JOIN inventory_asset_assignments aa ON aa.asset_id = a.id AND aa.released_at IS NULL
            LEFT JOIN warehouse_locations wl ON wl.id = aa.warehouse_location_id LEFT JOIN warehouses w ON w.id = wl.warehouse_id
            LEFT JOIN customers cu ON cu.id = aa.customer_id LEFT JOIN towers t ON t.id = aa.tower_id LEFT JOIN pop_sites ps ON ps.id = aa.pop_site_id LEFT JOIN users tech ON tech.id = aa.technician_id";
    }

    /** @param array<string, mixed>|null $metadata */
    private function event(int $assetId, string $type, string $description, ?string $oldStatus, ?string $newStatus, ?int $assignmentId, ?int $actorId, ?array $metadata = null): void
    {
        $this->pdo->prepare('INSERT INTO inventory_asset_events (asset_id, event_type, description, old_status, new_status, assignment_id, metadata, actor_user_id, occurred_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())')->execute([
            $assetId, $type, $description, $oldStatus, $newStatus, $assignmentId, $metadata !== null ? json_encode($metadata, JSON_THROW_ON_ERROR) : null, $actorId,
        ]);
    }
}
