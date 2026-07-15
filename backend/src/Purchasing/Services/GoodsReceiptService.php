<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Services;

use PDO;
use SkyFi\Purchasing\Contracts\GoodsReceiptRepositoryContract;
use SkyFi\Purchasing\Contracts\PurchaseOrderRepositoryContract;
use SkyFi\Purchasing\DomainModels\GoodsReceipt;
use SkyFi\Purchasing\DTOs\GoodsReceiptData;
use SkyFi\Purchasing\DTOs\GoodsReceiptListFilters;
use SkyFi\Purchasing\Validators\GoodsReceiptValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class GoodsReceiptService
{
    public function __construct(
        private readonly GoodsReceiptRepositoryContract $repository,
        private readonly PurchaseOrderRepositoryContract $poRepository,
        private readonly GoodsReceiptValidator $validator,
        private readonly AuditLoggerContract $audit,
        private readonly PDO $pdo,
        private readonly PurchasingFinanceIntegrationService $finance,
    ) {
    }

    public function list(GoodsReceiptListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): GoodsReceipt
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Goods receipt not found.');
    }

    public function create(GoodsReceiptData $data, int $actorId, ?string $ip = null, ?string $agent = null): GoodsReceipt
    {
        $this->validator->validate($data);

        // Validate that the PO exists and is in a receivable state
        $po = $this->poRepository->find($data->purchaseOrderId);
        if ($po === null) {
            throw new NotFoundException('Purchase order not found.');
        }
        $poData = $po->toArray();
        if (!in_array($poData['status'], ['approved', 'sent', 'partially_received'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'This purchase order cannot receive goods in its current state.']]);
        }

        // Validate each item against PO item limits
        $poItems = $this->poRepository->getItems($data->purchaseOrderId);
        $poItemMap = [];
        foreach ($poItems as $poi) {
            $poItemMap[(int) $poi['id']] = $poi;
        }

        foreach ($data->items as $index => $item) {
            $poiId = (int) ($item['purchase_order_item_id'] ?? 0);
            if (!isset($poItemMap[$poiId])) {
                throw new ValidationException([['code' => 'invalid_po_item', 'detail' => "Line {$index}: Purchase order item not found."]]);
            }
            $poi = $poItemMap[$poiId];
            $remaining = (float) $poi['quantity_ordered'] - (float) $poi['quantity_received'];
            $totalIn = (float) ($item['quantity_accepted'] ?? 0) + (float) ($item['quantity_damaged'] ?? 0);
            if ($totalIn > $remaining + 0.0001) {
                throw new ValidationException([['code' => 'excess_receipt', 'detail' => "Line {$index}: Cannot receive more than remaining quantity ({$remaining})."]]);
            }
        }

        // Create goods receipt within a transaction
        try {
            $this->pdo->beginTransaction();

            $receipt = $this->repository->create($data, $actorId);

            // Update PO item received quantities
            foreach ($data->items as $item) {
                $poiId = (int) ($item['purchase_order_item_id'] ?? 0);
                $accepted = (float) ($item['quantity_accepted'] ?? 0);
                $damaged = (float) ($item['quantity_damaged'] ?? 0);
                $this->poRepository->updateItemReceived($poiId, $accepted + $damaged, $damaged);
            }

            // Determine if PO is now fully or partially received
            $updatedPoItems = $this->poRepository->getItems($data->purchaseOrderId);
            $allReceived = true;
            $anyReceived = false;
            foreach ($updatedPoItems as $poi) {
                if ((float) $poi['quantity_received'] > 0) {
                    $anyReceived = true;
                }
                if ((float) $poi['quantity_received'] < (float) $poi['quantity_ordered'] - 0.0001) {
                    $allReceived = false;
                }
            }
            $newPoStatus = $allReceived ? 'fully_received' : 'partially_received';
            $this->poRepository->updateStatus($data->purchaseOrderId, $newPoStatus, $actorId);

            // Create inventory stock-in entries for accepted items
            $this->createInventoryStockIn($receipt, $data, $actorId);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if ($e instanceof ValidationException || $e instanceof NotFoundException) {
                throw $e;
            }
            throw new ValidationException([['code' => 'receipt_failed', 'detail' => 'Failed to record goods receipt: ' . $e->getMessage()]]);
        }

        $result = $this->get($receipt->id());
        $this->audit->log($actorId, 'purchasing.receipt.recorded', 'goods_receipt', $result->id(), null, $result->toArray(), $ip, $agent);
        EventDispatcher::dispatch('purchasing.receipt.recorded', $result->toArray());
        $this->finance->tryPostReceipt($result->id(), $actorId);
        return $result;
    }

    /**
     * Create inventory stock-in records for accepted goods.
     * This integrates with the Inventory module by directly inserting stock movements.
     */
    private function createInventoryStockIn(GoodsReceipt $receipt, GoodsReceiptData $data, int $actorId): void
    {
        $receiptData = $receipt->toArray();
        $now = date('Y-m-d H:i:s');

        // Get warehouse default location if available
        $warehouseId = $data->warehouseId;

        foreach ($data->items as $item) {
            $accepted = (float) ($item['quantity_accepted'] ?? 0);
            if ($accepted <= 0) {
                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $locationId = isset($item['warehouse_location_id']) ? (int) $item['warehouse_location_id'] : null;
            $condition = $item['condition'] ?? 'available';

            // If no location specified, try to find a default location for the warehouse
            if ($locationId === null && $warehouseId !== null) {
                $locStmt = $this->pdo->prepare("SELECT id FROM warehouse_locations WHERE warehouse_id = ? AND status = 'active' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1");
                $locStmt->execute([$warehouseId]);
                $locationId = $locStmt->fetchColumn() ?: null;
            }

            if ($locationId === null) {
                // Skip if no location available — will be tracked in receipt only
                continue;
            }

            // Get unit cost from PO item
            $poiStmt = $this->pdo->prepare('SELECT unit_price FROM purchase_order_items WHERE id = ?');
            $poiStmt->execute([(int) ($item['purchase_order_item_id'] ?? 0)]);
            $unitCost = (float) ($poiStmt->fetchColumn() ?: 0);

            // Create stock movement
            $movementNumber = 'SM-' . date('Y') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $movementStmt = $this->pdo->prepare(
                'INSERT INTO inventory_stock_movements (movement_number, movement_type, status, reference_type, reference_number, occurred_at, posted_at, created_by, posted_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $movementStmt->execute([
                $movementNumber,
                'stock_in',
                'posted',
                'goods_receipt',
                $receiptData['receipt_number'] ?? '',
                $now,
                $now,
                $actorId,
                $actorId,
                $now,
            ]);
            $movementId = (int) $this->pdo->lastInsertId();

            // Create movement line
            $totalCost = $accepted * $unitCost;
            $lineStmt = $this->pdo->prepare(
                'INSERT INTO inventory_stock_movement_lines (movement_id, product_id, destination_location_id, destination_condition, quantity, unit_cost, total_cost)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $lineStmt->execute([$movementId, $productId, $locationId, $condition, $accepted, $unitCost, $totalCost]);

            // Update stock balance (upsert)
            $balanceStmt = $this->pdo->prepare(
                'INSERT INTO inventory_stock_balances (product_id, warehouse_location_id, stock_condition, quantity, average_unit_cost)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    average_unit_cost = CASE
                        WHEN quantity > 0 THEN ((average_unit_cost * quantity) + VALUES(quantity * average_unit_cost)) / (quantity + VALUES(quantity))
                        ELSE VALUES(average_unit_cost)
                    END,
                    quantity = quantity + VALUES(quantity)'
            );
            $balanceStmt->execute([$productId, $locationId, $condition, $accepted, $unitCost]);

            // Create finance posting placeholder for the movement
            $idempotencyKey = 'purchasing_gr_' . $receipt->id() . '_item_' . ($item['purchase_order_item_id'] ?? 0);
            $postingStmt = $this->pdo->prepare(
                'INSERT IGNORE INTO inventory_finance_postings (movement_id, idempotency_key, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)'
            );
            $postingStmt->execute([$movementId, $idempotencyKey, 'pending', $now, $now]);
        }
    }

    public function returnToSupplier(int $id, int $actorId, ?string $ip = null, ?string $agent = null): GoodsReceipt
    {
        $existing = $this->get($id);
        $receipt = $this->repository->markReturned($id);
        $this->audit->log($actorId, 'purchasing.receipt.returned', 'goods_receipt', $id, $existing->toArray(), $receipt->toArray(), $ip, $agent);
        return $this->get($id);
    }
}
