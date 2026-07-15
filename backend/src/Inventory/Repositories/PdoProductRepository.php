<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Repositories;

use PDO;
use SkyFi\Inventory\Contracts\ProductRepositoryContract;
use SkyFi\Inventory\DomainModels\InventoryProduct;
use SkyFi\Inventory\DTOs\ProductData;
use SkyFi\Inventory\DTOs\ProductListFilters;

final class PdoProductRepository implements ProductRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(ProductListFilters $filters): array
    {
        [$where, $parameters] = $this->filters($filters);
        $sorts = [
            'name' => 'p.name', 'sku' => 'p.sku', 'status' => 'p.status', 'standard_cost' => 'p.standard_cost',
            'total_stock' => 'total_stock', 'created_at' => 'p.created_at',
        ];
        $descending = str_starts_with($filters->sort, '-');
        $field = ltrim($filters->sort, '-');
        $order = ($sorts[$field] ?? 'p.created_at') . ($descending ? ' DESC' : ' ASC');
        $base = $this->baseSelect($filters->warehouseId);
        $having = $filters->lowStock ? ' HAVING total_stock <= p.reorder_level' : '';

        $countSql = "SELECT COUNT(*) FROM inventory_products p
            LEFT JOIN inventory_categories c ON c.id = p.category_id
            LEFT JOIN inventory_product_models m ON m.id = p.model_id
            LEFT JOIN inventory_brands b ON b.id = m.brand_id
            WHERE {$where}";
        $count = $this->pdo->prepare($countSql);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        if ($filters->lowStock) {
            $ids = $this->pdo->prepare("SELECT p.id {$base['joins']} WHERE {$where} GROUP BY p.id{$having}");
            $ids->execute([...$base['parameters'], ...$parameters]);
            $total = count($ids->fetchAll(PDO::FETCH_COLUMN));
        }

        $offset = ($filters->page - 1) * $filters->perPage;
        $sql = "SELECT {$base['columns']} {$base['joins']} WHERE {$where} GROUP BY p.id{$having} ORDER BY {$order} LIMIT {$filters->perPage} OFFSET {$offset}";
        $statement = $this->pdo->prepare($sql);
        $statement->execute([...$base['parameters'], ...$parameters]);
        $items = array_map(static fn(array $row): InventoryProduct => InventoryProduct::fromRow($row), $statement->fetchAll(PDO::FETCH_ASSOC));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => max(1, (int) ceil($total / $filters->perPage))];
    }

    public function find(int $id, bool $forUpdate = false): ?InventoryProduct
    {
        $base = $this->baseSelect(null);
        $sql = "SELECT {$base['columns']} {$base['joins']} WHERE p.id = ? AND p.deleted_at IS NULL GROUP BY p.id" . ($forUpdate ? ' FOR UPDATE' : '');
        $statement = $this->pdo->prepare($sql);
        $statement->execute([...$base['parameters'], $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $vendor = $this->pdo->prepare('SELECT pv.*, v.name AS vendor_name FROM inventory_product_vendors pv JOIN vendors v ON v.id = pv.vendor_id WHERE pv.product_id = ? ORDER BY pv.is_default DESC, v.name');
        $vendor->execute([$id]);
        $row['vendors'] = $vendor->fetchAll(PDO::FETCH_ASSOC);
        return InventoryProduct::fromRow($row);
    }

    public function create(ProductData $data, int $actorId): InventoryProduct
    {
        return $this->transaction(function () use ($data, $actorId): InventoryProduct {
            $values = $data->toArray();
            $columns = array_keys($values);
            $sql = 'INSERT INTO inventory_products (' . implode(', ', $columns) . ', created_by) VALUES (' . implode(', ', array_fill(0, count($columns) + 1, '?')) . ')';
            $this->pdo->prepare($sql)->execute([...array_values($values), $actorId]);
            $id = (int) $this->pdo->lastInsertId();
            $this->syncVendors($id, $data->vendors);
            return $this->find($id) ?? throw new \RuntimeException('Unable to load created product.');
        });
    }

    public function update(int $id, ProductData $data, int $actorId): InventoryProduct
    {
        return $this->transaction(function () use ($id, $data, $actorId): InventoryProduct {
            $values = $data->toArray();
            $sets = array_map(static fn(string $key): string => $key . ' = ?', array_keys($values));
            $this->pdo->prepare('UPDATE inventory_products SET ' . implode(', ', $sets) . ', updated_by = ? WHERE id = ? AND deleted_at IS NULL')->execute([...array_values($values), $actorId, $id]);
            $this->syncVendors($id, $data->vendors);
            return $this->find($id) ?? throw new \RuntimeException('Unable to load updated product.');
        });
    }

    public function softDelete(int $id, int $actorId): void
    {
        $this->pdo->prepare("UPDATE inventory_products SET status = 'inactive', updated_by = ?, deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND deleted_at IS NULL")->execute([$actorId, $id]);
    }

    public function existsReference(string $table, int $id): bool
    {
        $tables = ['inventory_assets' => 'product_id', 'inventory_stock_balances' => 'product_id', 'inventory_stock_movement_lines' => 'product_id', 'inventory_warehouse_transfer_lines' => 'product_id'];
        if (!isset($tables[$table])) {
            return false;
        }
        $statement = $this->pdo->prepare("SELECT 1 FROM {$table} WHERE {$tables[$table]} = ? LIMIT 1");
        $statement->execute([$id]);
        return (bool) $statement->fetchColumn();
    }

    public function stock(int $warehouseId = 0): array
    {
        $where = $warehouseId > 0 ? ' AND w.id = ?' : '';
        $statement = $this->pdo->prepare("SELECT p.id AS product_id, p.sku, p.name AS product_name, p.tracking_mode, u.symbol AS unit,
            w.id AS warehouse_id, w.code AS warehouse_code, w.name AS warehouse_name, l.id AS location_id, l.code AS location_code, l.name AS location_name,
            sb.stock_condition, sb.quantity, sb.average_unit_cost, ROUND(sb.quantity * sb.average_unit_cost, 4) AS stock_value
            FROM inventory_stock_balances sb
            JOIN inventory_products p ON p.id = sb.product_id
            JOIN inventory_units u ON u.id = p.unit_id
            JOIN warehouse_locations l ON l.id = sb.warehouse_location_id
            JOIN warehouses w ON w.id = l.warehouse_id
            WHERE p.deleted_at IS NULL AND l.deleted_at IS NULL AND w.deleted_at IS NULL{$where}
            ORDER BY w.name, p.name, l.name, sb.stock_condition");
        $statement->execute($warehouseId > 0 ? [$warehouseId] : []);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array{0: string, 1: array<int, mixed>} */
    private function filters(ProductListFilters $filters): array
    {
        $where = ['p.deleted_at IS NULL'];
        $parameters = [];
        if ($filters->search !== null) {
            $where[] = '(p.sku LIKE ? OR p.name LIKE ? OR p.barcode LIKE ? OR m.name LIKE ? OR b.name LIKE ?)';
            $like = '%' . $filters->search . '%';
            array_push($parameters, $like, $like, $like, $like, $like);
        }
        foreach ([['p.status', $filters->status], ['p.tracking_mode', $filters->trackingMode]] as [$column, $value]) {
            if ($value !== null) {
                $where[] = $column . ' = ?';
                $parameters[] = $value;
            }
        }
        if ($filters->categoryId !== null) {
            $where[] = 'p.category_id = ?';
            $parameters[] = $filters->categoryId;
        }
        if ($filters->brandId !== null) {
            $where[] = 'm.brand_id = ?';
            $parameters[] = $filters->brandId;
        }
        return [implode(' AND ', $where), $parameters];
    }

    /** @return array{columns: string, joins: string, parameters: array<int, mixed>} */
    private function baseSelect(?int $warehouseId): array
    {
        $balanceWarehouse = $warehouseId !== null ? ' AND wl.warehouse_id = ?' : '';
        $assetWarehouse = $warehouseId !== null ? ' AND awl.warehouse_id = ?' : '';
        return [
            'columns' => "p.*, c.name AS category_name, m.name AS model_name, b.id AS brand_id, b.name AS brand_name, u.name AS unit_name, u.symbol AS unit_symbol,
                COALESCE(MAX(bs.quantity_stock), 0) AS quantity_stock, COALESCE(MAX(ast.asset_stock), 0) AS serialized_stock,
                CASE WHEN p.tracking_mode = 'serialized' THEN COALESCE(MAX(ast.asset_stock), 0) ELSE COALESCE(MAX(bs.quantity_stock), 0) END AS total_stock",
            'joins' => "FROM inventory_products p
                JOIN inventory_categories c ON c.id = p.category_id
                JOIN inventory_units u ON u.id = p.unit_id
                LEFT JOIN inventory_product_models m ON m.id = p.model_id
                LEFT JOIN inventory_brands b ON b.id = m.brand_id
                LEFT JOIN (SELECT sb.product_id, SUM(sb.quantity) AS quantity_stock FROM inventory_stock_balances sb JOIN warehouse_locations wl ON wl.id = sb.warehouse_location_id WHERE sb.stock_condition = 'available'{$balanceWarehouse} GROUP BY sb.product_id) bs ON bs.product_id = p.id
                LEFT JOIN (SELECT a.product_id, COUNT(*) AS asset_stock FROM inventory_assets a JOIN inventory_asset_assignments aa ON aa.asset_id = a.id AND aa.released_at IS NULL JOIN warehouse_locations awl ON awl.id = aa.warehouse_location_id WHERE a.deleted_at IS NULL AND a.status NOT IN ('scrapped','retired','lost'){$assetWarehouse} GROUP BY a.product_id) ast ON ast.product_id = p.id",
            'parameters' => $warehouseId !== null ? [$warehouseId, $warehouseId] : [],
        ];
    }

    /** @param array<int, array<string, mixed>> $vendors */
    private function syncVendors(int $productId, array $vendors): void
    {
        $this->pdo->prepare('DELETE FROM inventory_product_vendors WHERE product_id = ?')->execute([$productId]);
        if ($vendors === []) {
            return;
        }
        $statement = $this->pdo->prepare('INSERT INTO inventory_product_vendors (product_id, vendor_id, vendor_sku, is_default, last_purchase_cost, lead_time_days) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($vendors as $vendor) {
            $statement->execute([
                $productId,
                (int) $vendor['vendor_id'],
                $vendor['vendor_sku'] ?? null,
                !empty($vendor['is_default']) ? 1 : 0,
                isset($vendor['last_purchase_cost']) ? number_format((float) $vendor['last_purchase_cost'], 4, '.', '') : null,
                isset($vendor['lead_time_days']) ? max(0, (int) $vendor['lead_time_days']) : null,
            ]);
        }
    }

    private function transaction(callable $callback): mixed
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
}
