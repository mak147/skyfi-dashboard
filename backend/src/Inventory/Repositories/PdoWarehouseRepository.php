<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Repositories;

use PDO;
use SkyFi\Inventory\Contracts\WarehouseRepositoryContract;
use SkyFi\Inventory\DomainModels\Warehouse;
use SkyFi\Inventory\DTOs\WarehouseData;

final class PdoWarehouseRepository implements WarehouseRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 20)));
        $where = ['w.deleted_at IS NULL'];
        $params = [];
        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $where[] = '(w.code LIKE ? OR w.name LIKE ? OR w.city LIKE ?)';
            $like = '%' . trim((string) $filters['search']) . '%';
            array_push($params, $like, $like, $like);
        }
        foreach (['status', 'type'] as $field) {
            if (trim((string) ($filters[$field] ?? '')) !== '') {
                $where[] = 'w.' . $field . ' = ?';
                $params[] = $filters[$field];
            }
        }
        $clause = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM warehouses w WHERE {$clause}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $sorts = ['name' => 'w.name', 'code' => 'w.code', 'status' => 'w.status', 'type' => 'w.type', 'created_at' => 'w.created_at'];
        $sort = (string) ($filters['sort'] ?? '-created_at');
        $direction = str_starts_with($sort, '-') ? 'DESC' : 'ASC';
        $order = $sorts[ltrim($sort, '-')] ?? 'w.created_at';
        $offset = ($page - 1) * $perPage;
        $statement = $this->pdo->prepare("SELECT w.*, u.name AS manager_name, COALESCE(ls.location_count, 0) AS location_count,
            COALESCE(bs.quantity_stock, 0) AS quantity_stock, COALESCE(bs.stock_value, 0) AS stock_value, COALESCE(ast.serialized_assets, 0) AS serialized_assets
            FROM warehouses w
            LEFT JOIN users u ON u.id = w.manager_user_id
            LEFT JOIN (SELECT warehouse_id, COUNT(*) AS location_count FROM warehouse_locations WHERE deleted_at IS NULL GROUP BY warehouse_id) ls ON ls.warehouse_id = w.id
            LEFT JOIN (SELECT l.warehouse_id, SUM(sb.quantity) AS quantity_stock, SUM(sb.quantity * sb.average_unit_cost) AS stock_value FROM warehouse_locations l JOIN inventory_stock_balances sb ON sb.warehouse_location_id = l.id WHERE l.deleted_at IS NULL GROUP BY l.warehouse_id) bs ON bs.warehouse_id = w.id
            LEFT JOIN (SELECT l.warehouse_id, COUNT(DISTINCT aa.asset_id) AS serialized_assets FROM warehouse_locations l JOIN inventory_asset_assignments aa ON aa.warehouse_location_id = l.id AND aa.released_at IS NULL WHERE l.deleted_at IS NULL GROUP BY l.warehouse_id) ast ON ast.warehouse_id = w.id
            WHERE {$clause} ORDER BY {$order} {$direction} LIMIT {$perPage} OFFSET {$offset}");
        $statement->execute($params);
        $items = array_map(static fn(array $row): Warehouse => Warehouse::fromRow($row), $statement->fetchAll(PDO::FETCH_ASSOC));
        return ['items' => $items, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'lastPage' => max(1, (int) ceil($total / $perPage))];
    }

    public function find(int $id): ?Warehouse
    {
        $statement = $this->pdo->prepare("SELECT w.*, u.name AS manager_name, COALESCE(ls.location_count, 0) AS location_count,
            COALESCE(bs.quantity_stock, 0) AS quantity_stock, COALESCE(bs.stock_value, 0) AS stock_value, COALESCE(ast.serialized_assets, 0) AS serialized_assets
            FROM warehouses w LEFT JOIN users u ON u.id = w.manager_user_id
            LEFT JOIN (SELECT warehouse_id, COUNT(*) AS location_count FROM warehouse_locations WHERE deleted_at IS NULL GROUP BY warehouse_id) ls ON ls.warehouse_id = w.id
            LEFT JOIN (SELECT l.warehouse_id, SUM(sb.quantity) AS quantity_stock, SUM(sb.quantity * sb.average_unit_cost) AS stock_value FROM warehouse_locations l JOIN inventory_stock_balances sb ON sb.warehouse_location_id = l.id WHERE l.deleted_at IS NULL GROUP BY l.warehouse_id) bs ON bs.warehouse_id = w.id
            LEFT JOIN (SELECT l.warehouse_id, COUNT(DISTINCT aa.asset_id) AS serialized_assets FROM warehouse_locations l JOIN inventory_asset_assignments aa ON aa.warehouse_location_id = l.id AND aa.released_at IS NULL WHERE l.deleted_at IS NULL GROUP BY l.warehouse_id) ast ON ast.warehouse_id = w.id
            WHERE w.id = ? AND w.deleted_at IS NULL");
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['locations'] = $this->locations($id);
        return Warehouse::fromRow($row);
    }

    public function create(WarehouseData $data, int $actorId): Warehouse
    {
        $values = $data->toArray();
        $columns = array_keys($values);
        $this->pdo->prepare('INSERT INTO warehouses (' . implode(', ', $columns) . ', created_by) VALUES (' . implode(', ', array_fill(0, count($columns) + 1, '?')) . ')')->execute([...array_values($values), $actorId]);
        $id = (int) $this->pdo->lastInsertId();
        $this->saveLocation($id, null, ['code' => 'DEFAULT', 'name' => 'Default Location', 'status' => 'active']);
        return $this->find($id) ?? throw new \RuntimeException('Unable to load created warehouse.');
    }

    public function update(int $id, WarehouseData $data, int $actorId): Warehouse
    {
        $values = $data->toArray();
        $sets = array_map(static fn(string $field): string => $field . ' = ?', array_keys($values));
        $this->pdo->prepare('UPDATE warehouses SET ' . implode(', ', $sets) . ', updated_by = ? WHERE id = ? AND deleted_at IS NULL')->execute([...array_values($values), $actorId, $id]);
        return $this->find($id) ?? throw new \RuntimeException('Unable to load updated warehouse.');
    }

    public function softDelete(int $id, int $actorId): void
    {
        $this->pdo->prepare("UPDATE warehouses SET status = 'closed', updated_by = ?, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND deleted_at IS NULL")->execute([$actorId, $id]);
    }

    public function locations(int $warehouseId, bool $includeInactive = true): array
    {
        $sql = 'SELECT l.*, p.name AS parent_name, COALESCE(bs.quantity_stock, 0) AS quantity_stock, COALESCE(bs.stock_value, 0) AS stock_value, COALESCE(ast.serialized_assets, 0) AS serialized_assets FROM warehouse_locations l LEFT JOIN warehouse_locations p ON p.id = l.parent_id LEFT JOIN (SELECT warehouse_location_id, SUM(quantity) AS quantity_stock, SUM(quantity * average_unit_cost) AS stock_value FROM inventory_stock_balances GROUP BY warehouse_location_id) bs ON bs.warehouse_location_id = l.id LEFT JOIN (SELECT warehouse_location_id, COUNT(DISTINCT asset_id) AS serialized_assets FROM inventory_asset_assignments WHERE released_at IS NULL AND warehouse_location_id IS NOT NULL GROUP BY warehouse_location_id) ast ON ast.warehouse_location_id = l.id WHERE l.warehouse_id = ? AND l.deleted_at IS NULL' . ($includeInactive ? '' : " AND l.status = 'active'") . ' ORDER BY l.code';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$warehouseId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveLocation(int $warehouseId, ?int $id, array $data): array
    {
        $values = [
            $warehouseId,
            isset($data['parent_id']) && $data['parent_id'] !== '' ? (int) $data['parent_id'] : null,
            strtoupper(trim((string) ($data['code'] ?? ''))),
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['description'] ?? '')) ?: null,
            (string) ($data['status'] ?? 'active'),
        ];
        if ($id === null) {
            $this->pdo->prepare('INSERT INTO warehouse_locations (warehouse_id, parent_id, code, name, description, status) VALUES (?, ?, ?, ?, ?, ?)')->execute($values);
            $id = (int) $this->pdo->lastInsertId();
        } else {
            $this->pdo->prepare('UPDATE warehouse_locations SET parent_id = ?, code = ?, name = ?, description = ?, status = ? WHERE id = ? AND warehouse_id = ? AND deleted_at IS NULL')->execute([$values[1], $values[2], $values[3], $values[4], $values[5], $id, $warehouseId]);
        }
        $statement = $this->pdo->prepare('SELECT * FROM warehouse_locations WHERE id = ? AND warehouse_id = ? AND deleted_at IS NULL');
        $statement->execute([$id, $warehouseId]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteLocation(int $warehouseId, int $id): void
    {
        $this->pdo->prepare("UPDATE warehouse_locations SET status = 'inactive', deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND warehouse_id = ? AND deleted_at IS NULL")->execute([$id, $warehouseId]);
    }

    public function hasInventory(int $warehouseId, ?int $locationId = null): bool
    {
        $locationClause = $locationId !== null ? 'l.id = ?' : 'l.warehouse_id = ?';
        $statement = $this->pdo->prepare("SELECT EXISTS(SELECT 1 FROM warehouse_locations l JOIN inventory_stock_balances sb ON sb.warehouse_location_id = l.id WHERE {$locationClause} AND sb.quantity > 0) OR EXISTS(SELECT 1 FROM warehouse_locations l JOIN inventory_asset_assignments aa ON aa.warehouse_location_id = l.id AND aa.released_at IS NULL WHERE {$locationClause})");
        $value = $locationId ?? $warehouseId;
        $statement->execute([$value, $value]);
        return (bool) $statement->fetchColumn();
    }
}
