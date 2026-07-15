<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Repositories;

use PDO;
use SkyFi\Inventory\Contracts\StockRepositoryContract;
use SkyFi\Inventory\DomainModels\StockMovement;
use SkyFi\Inventory\DTOs\StockMovementListFilters;
use SkyFi\Inventory\DTOs\StockOperationData;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class PdoStockRepository implements StockRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(StockMovementListFilters $filters): array
    {
        $where = ['1 = 1'];
        $parameters = [];
        if ($filters->search !== null) {
            $where[] = '(m.movement_number LIKE ? OR m.reference_number LIKE ? OR m.reason LIKE ?)';
            $like = '%' . $filters->search . '%';
            array_push($parameters, $like, $like, $like);
        }
        if ($filters->type !== null) {
            $where[] = 'm.movement_type = ?';
            $parameters[] = $filters->type;
        }
        if ($filters->productId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM inventory_stock_movement_lines ml WHERE ml.movement_id = m.id AND ml.product_id = ?)';
            $parameters[] = $filters->productId;
        }
        if ($filters->warehouseId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM inventory_stock_movement_lines ml LEFT JOIN warehouse_locations sl ON sl.id = ml.source_location_id LEFT JOIN warehouse_locations dl ON dl.id = ml.destination_location_id WHERE ml.movement_id = m.id AND (sl.warehouse_id = ? OR dl.warehouse_id = ?))';
            array_push($parameters, $filters->warehouseId, $filters->warehouseId);
        }
        if ($filters->dateFrom !== null) {
            $where[] = 'm.occurred_at >= ?';
            $parameters[] = $filters->dateFrom . ' 00:00:00';
        }
        if ($filters->dateTo !== null) {
            $where[] = 'm.occurred_at <= ?';
            $parameters[] = $filters->dateTo . ' 23:59:59';
        }
        $clause = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM inventory_stock_movements m WHERE {$clause}");
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $sorts = ['movement_number' => 'm.movement_number', 'movement_type' => 'm.movement_type', 'occurred_at' => 'm.occurred_at', 'created_at' => 'm.created_at'];
        $direction = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';
        $order = $sorts[ltrim($filters->sort, '-')] ?? 'm.occurred_at';
        $offset = ($filters->page - 1) * $filters->perPage;
        $statement = $this->pdo->prepare("SELECT m.*, u.name AS posted_by_name, v.name AS vendor_name, st.ticket_number AS support_ticket_number,
            COUNT(l.id) AS line_count, COALESCE(SUM(l.total_cost), 0) AS total_value, COALESCE(SUM(l.quantity), 0) AS total_quantity
            FROM inventory_stock_movements m JOIN users u ON u.id = m.posted_by LEFT JOIN vendors v ON v.id = m.vendor_id LEFT JOIN support_tickets st ON st.id = m.support_ticket_id LEFT JOIN inventory_stock_movement_lines l ON l.movement_id = m.id
            WHERE {$clause} GROUP BY m.id ORDER BY {$order} {$direction} LIMIT {$filters->perPage} OFFSET {$offset}");
        $statement->execute($parameters);
        $items = array_map(static fn(array $row): StockMovement => StockMovement::fromRow($row), $statement->fetchAll(PDO::FETCH_ASSOC));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => max(1, (int) ceil($total / $filters->perPage))];
    }

    public function find(int $id, bool $forUpdate = false): ?StockMovement
    {
        $statement = $this->pdo->prepare('SELECT m.*, u.name AS posted_by_name, v.name AS vendor_name, st.ticket_number AS support_ticket_number FROM inventory_stock_movements m JOIN users u ON u.id = m.posted_by LEFT JOIN vendors v ON v.id = m.vendor_id LEFT JOIN support_tickets st ON st.id = m.support_ticket_id WHERE m.id = ?' . ($forUpdate ? ' FOR UPDATE' : ''));
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $lines = $this->pdo->prepare("SELECT l.*, p.sku, p.name AS product_name, a.asset_tag, a.serial_number, sl.code AS source_location_code, sw.name AS source_warehouse_name, dl.code AS destination_location_code, dw.name AS destination_warehouse_name
            FROM inventory_stock_movement_lines l JOIN inventory_products p ON p.id = l.product_id LEFT JOIN inventory_assets a ON a.id = l.asset_id
            LEFT JOIN warehouse_locations sl ON sl.id = l.source_location_id LEFT JOIN warehouses sw ON sw.id = sl.warehouse_id
            LEFT JOIN warehouse_locations dl ON dl.id = l.destination_location_id LEFT JOIN warehouses dw ON dw.id = dl.warehouse_id WHERE l.movement_id = ? ORDER BY l.id");
        $lines->execute([$id]);
        $row['lines'] = $lines->fetchAll(PDO::FETCH_ASSOC);
        return StockMovement::fromRow($row);
    }

    public function post(StockOperationData $data, int $actorId): StockMovement
    {
        return $this->transaction(function () use ($data, $actorId): StockMovement {
            $number = $this->number('MOV');
            $statement = $this->pdo->prepare('INSERT INTO inventory_stock_movements (movement_number, movement_type, reference_type, reference_number, support_ticket_id, vendor_id, reason, notes, occurred_at, posted_at, created_by, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?)');
            $statement->execute([$number, $data->type, $data->referenceType, $data->referenceNumber, $data->supportTicketId, $data->vendorId, $data->reason, $data->notes, $data->occurredAt, $actorId, $actorId]);
            $movementId = (int) $this->pdo->lastInsertId();
            foreach ($data->lines as $line) {
                $product = $this->lockProduct((int) ($line['product_id'] ?? 0));
                if ($product['tracking_mode'] === 'serialized') {
                    $this->postSerializedLine($movementId, $data->type, $product, $line, $data->vendorId, $actorId);
                } else {
                    $this->postQuantityLine($movementId, $data->type, $product, $line);
                }
            }
            $this->queueFinancePosting($movementId, $data->type);
            return $this->find($movementId) ?? throw new \RuntimeException('Unable to load posted movement.');
        });
    }

    public function reverse(int $id, string $reason, int $actorId): StockMovement
    {
        if (trim($reason) === '') {
            throw new ValidationException([$this->error('reason', 'A reversal reason is required.')]);
        }
        return $this->transaction(function () use ($id, $reason, $actorId): StockMovement {
            $original = $this->find($id, true) ?? throw new NotFoundException('Stock movement not found.');
            if (($original->toArray()['status'] ?? '') !== 'posted') {
                throw new ValidationException([$this->error('movement', 'Only a posted movement can be reversed.')]);
            }
            $number = $this->number('REV');
            $this->pdo->prepare("INSERT INTO inventory_stock_movements (movement_number, movement_type, status, reference_type, reference_number, reversal_of_id, reason, occurred_at, posted_at, created_by, posted_by) VALUES (?, 'reversal', 'posted', 'stock_movement', ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), ?, ?)")->execute([$number, (string) $original->toArray()['movement_number'], $id, $reason, $actorId, $actorId]);
            $reverseId = (int) $this->pdo->lastInsertId();
            foreach ($original->toArray()['lines'] as $line) {
                $product = $this->lockProduct((int) $line['product_id']);
                $inverse = [
                    'product_id' => (int) $line['product_id'],
                    'asset_id' => $line['asset_id'] !== null ? (int) $line['asset_id'] : null,
                    'quantity' => $line['quantity'],
                    'source_location_id' => $line['destination_location_id'],
                    'destination_location_id' => $line['source_location_id'],
                    'source_condition' => $line['destination_condition'],
                    'destination_condition' => $line['source_condition'],
                    'unit_cost' => $line['unit_cost'],
                ];
                if ($product['tracking_mode'] === 'serialized') {
                    $this->reverseSerializedLine($reverseId, $product, $inverse, $actorId);
                } else {
                    $this->applyQuantityLine($reverseId, $product, $inverse);
                }
            }
            $this->pdo->prepare("UPDATE inventory_stock_movements SET status = 'reversed' WHERE id = ?")->execute([$id]);
            $this->queueFinancePosting($reverseId, 'reversal');
            return $this->find($reverseId) ?? throw new \RuntimeException('Unable to load reversal.');
        });
    }

    public function balances(array $filters): array
    {
        $where = ['p.deleted_at IS NULL', 'w.deleted_at IS NULL', 'l.deleted_at IS NULL'];
        $parameters = [];
        foreach ([['p.id', 'product_id'], ['w.id', 'warehouse_id'], ['l.id', 'location_id']] as [$column, $key]) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $where[] = $column . ' = ?';
                $parameters[] = (int) $filters[$key];
            }
        }
        if (trim((string) ($filters['condition'] ?? '')) !== '') {
            $where[] = 'sb.stock_condition = ?';
            $parameters[] = $filters['condition'];
        }
        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $where[] = '(p.sku LIKE ? OR p.name LIKE ? OR w.name LIKE ? OR l.name LIKE ?)';
            $like = '%' . trim((string) $filters['search']) . '%';
            array_push($parameters, $like, $like, $like, $like);
        }
        $statement = $this->pdo->prepare("SELECT sb.*, p.sku, p.name AS product_name, p.reorder_level, p.minimum_stock, u.symbol AS unit_symbol, w.id AS warehouse_id, w.code AS warehouse_code, w.name AS warehouse_name, l.code AS location_code, l.name AS location_name, ROUND(sb.quantity * sb.average_unit_cost, 4) AS stock_value, (sb.quantity <= p.reorder_level) AS is_low_stock
            FROM inventory_stock_balances sb JOIN inventory_products p ON p.id = sb.product_id JOIN inventory_units u ON u.id = p.unit_id JOIN warehouse_locations l ON l.id = sb.warehouse_location_id JOIN warehouses w ON w.id = l.warehouse_id
            WHERE " . implode(' AND ', $where) . ' ORDER BY w.name, p.name, l.name, sb.stock_condition');
        $statement->execute($parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function dashboard(): array
    {
        $single = static fn(PDO $pdo, string $sql): string => (string) $pdo->query($sql)->fetchColumn();
        return [
            'total_products' => (int) $single($this->pdo, 'SELECT COUNT(*) FROM inventory_products WHERE deleted_at IS NULL'),
            'total_assets' => (int) $single($this->pdo, 'SELECT COUNT(*) FROM inventory_assets WHERE deleted_at IS NULL'),
            'active_warehouses' => (int) $single($this->pdo, "SELECT COUNT(*) FROM warehouses WHERE deleted_at IS NULL AND status = 'active'"),
            'stock_value' => $single($this->pdo, 'SELECT COALESCE(SUM(quantity * average_unit_cost), 0) FROM inventory_stock_balances'),
            'serialized_asset_value' => $single($this->pdo, "SELECT COALESCE(SUM(acquisition_cost), 0) FROM inventory_assets WHERE deleted_at IS NULL AND status NOT IN ('scrapped','retired','lost')"),
            'low_stock_products' => (int) $single($this->pdo, "SELECT COUNT(*) FROM inventory_products p WHERE p.deleted_at IS NULL AND p.tracking_mode = 'quantity' AND (SELECT COALESCE(SUM(sb.quantity), 0) FROM inventory_stock_balances sb WHERE sb.product_id = p.id AND sb.stock_condition = 'available') <= p.reorder_level"),
            'damaged_quantity' => $single($this->pdo, "SELECT COALESCE(SUM(quantity), 0) FROM inventory_stock_balances WHERE stock_condition = 'damaged'"),
            'damaged_assets' => (int) $single($this->pdo, "SELECT COUNT(*) FROM inventory_assets WHERE deleted_at IS NULL AND status = 'damaged'"),
            'pending_transfers' => (int) $single($this->pdo, "SELECT COUNT(*) FROM inventory_warehouse_transfers WHERE status IN ('pending','approved','in_transit','partially_received')"),
            'asset_statuses' => $this->pdo->query('SELECT status, COUNT(*) AS total FROM inventory_assets WHERE deleted_at IS NULL GROUP BY status ORDER BY total DESC')->fetchAll(PDO::FETCH_ASSOC),
            'warehouse_stock' => $this->pdo->query('SELECT w.id, w.name, COALESCE(SUM(sb.quantity * sb.average_unit_cost), 0) AS stock_value FROM warehouses w LEFT JOIN warehouse_locations l ON l.warehouse_id = w.id AND l.deleted_at IS NULL LEFT JOIN inventory_stock_balances sb ON sb.warehouse_location_id = l.id WHERE w.deleted_at IS NULL GROUP BY w.id ORDER BY stock_value DESC')->fetchAll(PDO::FETCH_ASSOC),
            'recent_movements' => $this->pdo->query('SELECT id, movement_number, movement_type, status, occurred_at, reason FROM inventory_stock_movements ORDER BY occurred_at DESC LIMIT 8')->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function accountingSettings(): array
    {
        $row = $this->pdo->query('SELECT s.*, ia.name AS inventory_asset_account_name, ic.name AS inventory_clearing_account_name, cogs.name AS cogs_account_name, adj.name AS adjustment_account_name, ds.name AS damage_scrap_account_name FROM inventory_accounting_settings s LEFT JOIN chart_of_accounts ia ON ia.id = s.inventory_asset_account_id LEFT JOIN chart_of_accounts ic ON ic.id = s.inventory_clearing_account_id LEFT JOIN chart_of_accounts cogs ON cogs.id = s.cogs_account_id LEFT JOIN chart_of_accounts adj ON adj.id = s.adjustment_account_id LEFT JOIN chart_of_accounts ds ON ds.id = s.damage_scrap_account_id WHERE s.id = 1')->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function updateAccountingSettings(array $data, int $actorId): array
    {
        $keys = ['inventory_asset_account_id', 'inventory_clearing_account_id', 'cogs_account_id', 'adjustment_account_id', 'damage_scrap_account_id'];
        $values = [];
        foreach ($keys as $key) {
            $values[] = isset($data[$key]) && $data[$key] !== '' ? (int) $data[$key] : null;
        }
        $this->pdo->prepare('UPDATE inventory_accounting_settings SET inventory_asset_account_id = ?, inventory_clearing_account_id = ?, cogs_account_id = ?, adjustment_account_id = ?, damage_scrap_account_id = ?, updated_by = ? WHERE id = 1')->execute([...$values, $actorId]);
        return $this->accountingSettings();
    }

    public function financePostings(): array
    {
        return $this->pdo->query('SELECT fp.*, m.movement_number, m.movement_type, m.occurred_at FROM inventory_finance_postings fp JOIN inventory_stock_movements m ON m.id = fp.movement_id ORDER BY fp.id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function transaction(callable $callback): mixed
    {
        $owns = !$this->pdo->inTransaction();
        if ($owns) {
            $this->pdo->beginTransaction();
        }
        try {
            $result = $callback();
            if ($owns) {
                $this->pdo->commit();
            }
            return $result;
        } catch (\Throwable $exception) {
            if ($owns && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $product @param array<string, mixed> $line */
    private function postQuantityLine(int $movementId, string $type, array $product, array $line): void
    {
        $sourceTypes = ['stock_out', 'adjustment_out', 'damaged', 'scrap', 'transfer_dispatch'];
        $destinationTypes = ['opening_balance', 'stock_in', 'adjustment_in', 'return', 'damaged', 'transfer_receipt'];
        $sourceId = in_array($type, $sourceTypes, true) ? $this->requiredId($line, 'source_location_id') : null;
        $destinationId = in_array($type, $destinationTypes, true) ? $this->requiredId($line, 'destination_location_id', $type === 'damaged' ? $sourceId : null) : null;
        $sourceCondition = $sourceId !== null ? (string) ($line['source_condition'] ?? 'available') : null;
        $destinationCondition = $destinationId !== null ? (string) ($line['destination_condition'] ?? ($type === 'damaged' ? 'damaged' : 'available')) : null;
        $quantity = $this->quantity($line['quantity'] ?? 0);
        if ($type === 'opening_balance') {
            $check = $this->pdo->prepare('SELECT 1 FROM inventory_stock_movement_lines l JOIN inventory_stock_movements m ON m.id = l.movement_id WHERE l.product_id = ? AND l.destination_location_id = ? LIMIT 1');
            $check->execute([(int) $product['id'], $destinationId]);
            if ($check->fetchColumn()) {
                throw new ValidationException([$this->error('lines', 'Opening balance is only allowed before movement history exists for this product and location.')]);
            }
        }
        $sourceCost = null;
        if ($sourceId !== null) {
            $sourceCost = $this->decreaseBalance((int) $product['id'], $sourceId, $sourceCondition, $quantity);
        }
        $unitCost = $sourceCost ?? number_format(is_numeric($line['unit_cost'] ?? null) ? (float) $line['unit_cost'] : (float) $product['standard_cost'], 4, '.', '');
        if ($destinationId !== null) {
            $this->increaseBalance((int) $product['id'], $destinationId, $destinationCondition, $quantity, $unitCost);
        }
        $this->line($movementId, (int) $product['id'], null, $sourceId, $destinationId, $sourceCondition, $destinationCondition, $quantity, $unitCost, $line['notes'] ?? null);
    }

    /** @param array<string, mixed> $product @param array<string, mixed> $line */
    private function postSerializedLine(int $movementId, string $type, array $product, array $line, ?int $vendorId, int $actorId): void
    {
        $assetIds = array_values(array_unique(array_map('intval', is_array($line['asset_ids'] ?? null) ? $line['asset_ids'] : [])));
        if (is_array($line['assets'] ?? null) && in_array($type, ['opening_balance', 'stock_in'], true)) {
            foreach ($line['assets'] as $assetData) {
                if (!is_array($assetData)) {
                    continue;
                }
                $assetIds[] = $this->createReceivedAsset((int) $product['id'], $assetData, $vendorId, $actorId);
            }
        }
        if (isset($line['asset_id'])) {
            $assetIds[] = (int) $line['asset_id'];
        }
        $assetIds = array_values(array_filter(array_unique($assetIds), static fn(int $id): bool => $id > 0));
        if ($assetIds === []) {
            throw new ValidationException([$this->error('asset_ids', 'Serialized stock lines require at least one asset.')]);
        }
        $declared = (float) ($line['quantity'] ?? count($assetIds));
        if (abs($declared - count($assetIds)) > 0.0001) {
            throw new ValidationException([$this->error('quantity', 'Serialized quantity must equal the number of selected assets.')]);
        }
        foreach ($assetIds as $assetId) {
            $asset = $this->lockAsset($assetId, (int) $product['id']);
            $currentLocation = $this->currentAssetLocation($assetId);
            $requestedSource = $this->optionalId($line, 'source_location_id');
            $sourceId = in_array($type, ['stock_out', 'adjustment_out', 'damaged', 'scrap', 'transfer_dispatch'], true) ? ($currentLocation ?? $requestedSource) : null;
            if ($type === 'transfer_dispatch' && ($currentLocation === null || $requestedSource === null || $currentLocation !== $requestedSource)) {
                throw new ValidationException([$this->error('asset_ids', 'Every transferred asset must currently be assigned to the selected source location.')]);
            }
            $destinationId = in_array($type, ['opening_balance', 'stock_in', 'adjustment_in', 'return', 'transfer_receipt'], true) ? $this->requiredId($line, 'destination_location_id') : ($type === 'damaged' ? ($currentLocation ?? $this->requiredId($line, 'destination_location_id')) : null);
            $unitCost = number_format(is_numeric($line['unit_cost'] ?? null) ? (float) $line['unit_cost'] : (float) ($asset['acquisition_cost'] ?: $product['standard_cost']), 4, '.', '');
            $this->line($movementId, (int) $product['id'], $assetId, $sourceId, $destinationId, $sourceId ? 'available' : null, $destinationId ? ($type === 'damaged' ? 'damaged' : 'available') : null, '1.0000', $unitCost, $line['notes'] ?? null);
            if ($destinationId !== null) {
                $this->assignAssetWarehouse($assetId, $destinationId, $type === 'damaged' ? 'damaged' : 'in_stock', $actorId, $movementId);
            } elseif ($type === 'scrap') {
                $this->releaseAsset($assetId, 'scrapped', $actorId, $movementId);
            } elseif ($type === 'damaged') {
                $this->releaseAsset($assetId, 'damaged', $actorId, $movementId);
            } elseif ($type === 'stock_out') {
                $assignment = is_array($line['assignment'] ?? null) ? $line['assignment'] : null;
                if ($assignment !== null) {
                    $this->assignAssetTarget($assetId, $assignment, $actorId, $movementId);
                } else {
                    $this->releaseAsset($assetId, 'assigned', $actorId, $movementId);
                }
            } elseif ($type === 'transfer_dispatch') {
                $this->releaseAsset($assetId, 'in_transit', $actorId, $movementId);
            } elseif ($type === 'adjustment_out') {
                $this->releaseAsset($assetId, 'lost', $actorId, $movementId);
            }
        }
    }

    /** @param array<string, mixed> $product @param array<string, mixed> $line */
    private function applyQuantityLine(int $movementId, array $product, array $line): void
    {
        $sourceId = $this->optionalId($line, 'source_location_id');
        $destinationId = $this->optionalId($line, 'destination_location_id');
        $sourceCondition = $sourceId !== null ? (string) ($line['source_condition'] ?? 'available') : null;
        $destinationCondition = $destinationId !== null ? (string) ($line['destination_condition'] ?? 'available') : null;
        $quantity = $this->quantity($line['quantity'] ?? 0);
        $sourceCost = $sourceId !== null ? $this->decreaseBalance((int) $product['id'], $sourceId, $sourceCondition, $quantity) : null;
        $unitCost = $sourceCost ?? number_format((float) ($line['unit_cost'] ?? $product['standard_cost']), 4, '.', '');
        if ($destinationId !== null) {
            $this->increaseBalance((int) $product['id'], $destinationId, $destinationCondition, $quantity, $unitCost);
        }
        $this->line($movementId, (int) $product['id'], null, $sourceId, $destinationId, $sourceCondition, $destinationCondition, $quantity, $unitCost, null);
    }

    /** @param array<string, mixed> $product @param array<string, mixed> $line */
    private function reverseSerializedLine(int $movementId, array $product, array $line, int $actorId): void
    {
        $assetId = (int) ($line['asset_id'] ?? 0);
        $this->lockAsset($assetId, (int) $product['id']);
        $sourceId = $this->optionalId($line, 'source_location_id');
        $destinationId = $this->optionalId($line, 'destination_location_id');
        $unitCost = number_format((float) ($line['unit_cost'] ?? 0), 4, '.', '');
        $this->line($movementId, (int) $product['id'], $assetId, $sourceId, $destinationId, $line['source_condition'] ?? null, $line['destination_condition'] ?? null, '1.0000', $unitCost, 'Reversal');
        if ($destinationId !== null) {
            $this->assignAssetWarehouse($assetId, $destinationId, 'in_stock', $actorId, $movementId);
        } else {
            $this->releaseAsset($assetId, 'returned', $actorId, $movementId);
        }
    }

    /** @return array<string, mixed> */
    private function lockProduct(int $id): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM inventory_products WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new NotFoundException('Inventory product not found.');
        }
        if ($row['status'] !== 'active') {
            throw new ValidationException([$this->error('product_id', 'Stock operations require an active product.')]);
        }
        return $row;
    }

    /** @return array<string, mixed> */
    private function lockAsset(int $id, int $productId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM inventory_assets WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int) $row['product_id'] !== $productId) {
            throw new ValidationException([$this->error('asset_ids', 'An asset does not belong to the selected product.')]);
        }
        if (in_array($row['status'], ['scrapped', 'retired'], true)) {
            throw new ValidationException([$this->error('asset_ids', 'Scrapped or retired assets cannot be moved.')]);
        }
        return $row;
    }

    private function increaseBalance(int $productId, int $locationId, string $condition, string $quantity, string $unitCost): void
    {
        $this->assertActiveLocation($locationId);
        $this->pdo->prepare('INSERT IGNORE INTO inventory_stock_balances (product_id, warehouse_location_id, stock_condition, quantity, average_unit_cost) VALUES (?, ?, ?, 0, 0)')->execute([$productId, $locationId, $condition]);
        $statement = $this->pdo->prepare('SELECT quantity, average_unit_cost FROM inventory_stock_balances WHERE product_id = ? AND warehouse_location_id = ? AND stock_condition = ? FOR UPDATE');
        $statement->execute([$productId, $locationId, $condition]);
        $current = $statement->fetch(PDO::FETCH_ASSOC);
        $oldQuantity = (float) $current['quantity'];
        $newQuantity = $oldQuantity + (float) $quantity;
        $average = $newQuantity > 0 ? (($oldQuantity * (float) $current['average_unit_cost']) + ((float) $quantity * (float) $unitCost)) / $newQuantity : 0;
        $this->pdo->prepare('UPDATE inventory_stock_balances SET quantity = ?, average_unit_cost = ? WHERE product_id = ? AND warehouse_location_id = ? AND stock_condition = ?')->execute([number_format($newQuantity, 4, '.', ''), number_format($average, 4, '.', ''), $productId, $locationId, $condition]);
    }

    private function decreaseBalance(int $productId, int $locationId, string $condition, string $quantity): string
    {
        $statement = $this->pdo->prepare('SELECT quantity, average_unit_cost FROM inventory_stock_balances WHERE product_id = ? AND warehouse_location_id = ? AND stock_condition = ? FOR UPDATE');
        $statement->execute([$productId, $locationId, $condition]);
        $current = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$current || (float) $current['quantity'] + 0.00001 < (float) $quantity) {
            throw new ValidationException([$this->error('quantity', 'Insufficient stock is available at the selected location.')]);
        }
        $this->pdo->prepare('UPDATE inventory_stock_balances SET quantity = quantity - ? WHERE product_id = ? AND warehouse_location_id = ? AND stock_condition = ?')->execute([$quantity, $productId, $locationId, $condition]);
        return number_format((float) $current['average_unit_cost'], 4, '.', '');
    }

    private function line(int $movementId, int $productId, ?int $assetId, ?int $sourceId, ?int $destinationId, ?string $sourceCondition, ?string $destinationCondition, string $quantity, string $unitCost, ?string $notes): void
    {
        $total = number_format((float) $quantity * (float) $unitCost, 4, '.', '');
        $this->pdo->prepare('INSERT INTO inventory_stock_movement_lines (movement_id, product_id, asset_id, source_location_id, destination_location_id, source_condition, destination_condition, quantity, unit_cost, total_cost, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$movementId, $productId, $assetId, $sourceId, $destinationId, $sourceCondition, $destinationCondition, $quantity, $unitCost, $total, $notes]);
    }

    private function createReceivedAsset(int $productId, array $data, ?int $vendorId, int $actorId): int
    {
        $assetTag = strtoupper(trim((string) ($data['asset_tag'] ?? '')));
        $serial = trim((string) ($data['serial_number'] ?? ''));
        if ($assetTag === '' || $serial === '') {
            throw new ValidationException([$this->error('assets', 'Every received serialized asset requires an asset tag and serial number.')]);
        }
        $mac = trim((string) ($data['mac_address'] ?? ''));
        $mac = $mac === '' ? null : strtoupper(str_replace('-', ':', $mac));
        if ($mac !== null && !filter_var($mac, FILTER_VALIDATE_MAC)) {
            throw new ValidationException([$this->error('mac_address', 'A received asset has an invalid MAC address.')]);
        }
        $this->pdo->prepare("INSERT INTO inventory_assets (product_id, vendor_id, asset_tag, serial_number, mac_address, imei, barcode, purchase_date, acquisition_cost, warranty_starts_at, warranty_expires_at, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'in_stock', ?, ?)")->execute([
            $productId, $vendorId, $assetTag, $serial, $mac, $data['imei'] ?? null, $data['barcode'] ?? null, $data['purchase_date'] ?? null,
            number_format((float) ($data['acquisition_cost'] ?? 0), 4, '.', ''), $data['warranty_starts_at'] ?? null, $data['warranty_expires_at'] ?? null, $data['notes'] ?? null, $actorId,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $this->assetEvent($id, 'received', 'Serialized asset was received into inventory.', null, 'in_stock', $actorId, null);
        return $id;
    }

    private function currentAssetLocation(int $assetId): ?int
    {
        $statement = $this->pdo->prepare("SELECT warehouse_location_id FROM inventory_asset_assignments WHERE asset_id = ? AND released_at IS NULL AND assignment_type = 'warehouse' FOR UPDATE");
        $statement->execute([$assetId]);
        $value = $statement->fetchColumn();
        return $value !== false ? (int) $value : null;
    }

    private function assignAssetWarehouse(int $assetId, int $locationId, string $status, int $actorId, int $movementId): void
    {
        $this->assertActiveLocation($locationId);
        $this->pdo->prepare('UPDATE inventory_asset_assignments SET released_at = UTC_TIMESTAMP(), released_by = ? WHERE asset_id = ? AND released_at IS NULL')->execute([$actorId, $assetId]);
        $this->pdo->prepare("INSERT INTO inventory_asset_assignments (asset_id, assignment_type, warehouse_location_id, assigned_by, assigned_at, notes) VALUES (?, 'warehouse', ?, ?, UTC_TIMESTAMP(), ?)")->execute([$assetId, $locationId, $actorId, 'Stock movement #' . $movementId]);
        $assignmentId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare('UPDATE inventory_assets SET status = ?, updated_by = ? WHERE id = ?')->execute([$status, $actorId, $assetId]);
        $this->assetEvent($assetId, 'stock_moved', 'Asset moved by a stock operation.', null, $status, $actorId, $assignmentId, ['movement_id' => $movementId]);
    }

    /** @param array<string, mixed> $assignment */
    private function assignAssetTarget(int $assetId, array $assignment, int $actorId, int $movementId): void
    {
        $types = ['customer' => 'customer_id', 'tower' => 'tower_id', 'pop_site' => 'pop_site_id', 'technician' => 'technician_id'];
        $type = (string) ($assignment['assignment_type'] ?? '');
        $column = $types[$type] ?? null;
        $target = $column !== null ? (int) ($assignment[$column] ?? 0) : 0;
        if ($column === null || $target < 1) {
            throw new ValidationException([$this->error('assignment', 'Serialized stock-out assignment is invalid.')]);
        }
        $this->pdo->prepare('UPDATE inventory_asset_assignments SET released_at = UTC_TIMESTAMP(), released_by = ? WHERE asset_id = ? AND released_at IS NULL')->execute([$actorId, $assetId]);
        $sql = "INSERT INTO inventory_asset_assignments (asset_id, assignment_type, {$column}, assigned_by, assigned_at, notes) VALUES (?, ?, ?, ?, UTC_TIMESTAMP(), ?)";
        $this->pdo->prepare($sql)->execute([$assetId, $type, $target, $actorId, 'Stock movement #' . $movementId]);
        $assignmentId = (int) $this->pdo->lastInsertId();
        $status = $type === 'technician' ? 'assigned' : 'deployed';
        $this->pdo->prepare('UPDATE inventory_assets SET status = ?, updated_by = ? WHERE id = ?')->execute([$status, $actorId, $assetId]);
        $this->assetEvent($assetId, 'deployed', 'Asset issued and assigned.', null, $status, $actorId, $assignmentId, ['movement_id' => $movementId]);
    }

    private function releaseAsset(int $assetId, string $status, int $actorId, int $movementId): void
    {
        $this->pdo->prepare('UPDATE inventory_asset_assignments SET released_at = UTC_TIMESTAMP(), released_by = ? WHERE asset_id = ? AND released_at IS NULL')->execute([$actorId, $assetId]);
        $this->pdo->prepare('UPDATE inventory_assets SET status = ?, updated_by = ? WHERE id = ?')->execute([$status, $actorId, $assetId]);
        $this->assetEvent($assetId, $status, 'Asset status changed by stock operation.', null, $status, $actorId, null, ['movement_id' => $movementId]);
    }

    /** @param array<string, mixed>|null $metadata */
    private function assetEvent(int $assetId, string $type, string $description, ?string $oldStatus, ?string $newStatus, int $actorId, ?int $assignmentId = null, ?array $metadata = null): void
    {
        $this->pdo->prepare('INSERT INTO inventory_asset_events (asset_id, event_type, description, old_status, new_status, assignment_id, metadata, actor_user_id, occurred_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())')->execute([$assetId, $type, $description, $oldStatus, $newStatus, $assignmentId, $metadata !== null ? json_encode($metadata, JSON_THROW_ON_ERROR) : null, $actorId]);
    }

    private function assertActiveLocation(int $locationId): void
    {
        $statement = $this->pdo->prepare("SELECT 1 FROM warehouse_locations l JOIN warehouses w ON w.id = l.warehouse_id WHERE l.id = ? AND l.deleted_at IS NULL AND l.status = 'active' AND w.deleted_at IS NULL AND w.status = 'active'");
        $statement->execute([$locationId]);
        if (!$statement->fetchColumn()) {
            throw new ValidationException([$this->error('warehouse_location_id', 'The selected warehouse location is not active.')]);
        }
    }

    private function queueFinancePosting(int $movementId, string $type): void
    {
        $notRequired = in_array($type, ['transfer_dispatch', 'transfer_receipt'], true);
        $this->pdo->prepare('INSERT INTO inventory_finance_postings (movement_id, idempotency_key, status) VALUES (?, ?, ?)')->execute([$movementId, 'inventory-movement-' . $movementId, $notRequired ? 'not_required' : 'pending']);
    }

    /** @return array<string, mixed> */
    private function error(string $field, string $detail): array
    {
        return ['code' => 'validation_error', 'detail' => $detail, 'source' => ['pointer' => '/data/attributes/' . $field]];
    }

    private function quantity(mixed $value): string
    {
        if (!is_numeric($value) || (float) $value <= 0) {
            throw new ValidationException([$this->error('quantity', 'Quantity must be greater than zero.')]);
        }
        return number_format((float) $value, 4, '.', '');
    }

    /** @param array<string, mixed> $line */
    private function requiredId(array $line, string $key, ?int $fallback = null): int
    {
        $id = isset($line[$key]) && $line[$key] !== '' ? (int) $line[$key] : ($fallback ?? 0);
        if ($id < 1) {
            throw new ValidationException([$this->error($key, 'A warehouse location is required for this operation.')]);
        }
        return $id;
    }

    /** @param array<string, mixed> $line */
    private function optionalId(array $line, string $key): ?int
    {
        return isset($line[$key]) && $line[$key] !== '' ? (int) $line[$key] : null;
    }

    private function number(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, gmdate('YmdHis'), strtoupper(bin2hex(random_bytes(3))));
    }
}
