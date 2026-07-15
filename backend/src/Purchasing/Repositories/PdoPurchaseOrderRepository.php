<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Repositories;

use PDO;
use SkyFi\Purchasing\Contracts\PurchaseOrderRepositoryContract;
use SkyFi\Purchasing\DomainModels\PurchaseOrder;
use SkyFi\Purchasing\DTOs\PurchaseOrderData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;

final class PdoPurchaseOrderRepository implements PurchaseOrderRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(PurchaseOrderListFilters $filters): array
    {
        $where = ['po.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null && $filters->search !== '') {
            $where[] = '(po.po_number LIKE :search OR po.notes LIKE :search2 OR v.name LIKE :search3)';
            $params['search'] = $params['search2'] = $params['search3'] = '%' . $filters->search . '%';
        }
        if ($filters->status !== null) {
            $where[] = 'po.status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->vendorId !== null) {
            $where[] = 'po.vendor_id = :vendor_id';
            $params['vendor_id'] = $filters->vendorId;
        }
        if ($filters->dateFrom !== null) {
            $where[] = 'po.order_date >= :date_from';
            $params['date_from'] = $filters->dateFrom;
        }
        if ($filters->dateTo !== null) {
            $where[] = 'po.order_date <= :date_to';
            $params['date_to'] = $filters->dateTo;
        }

        $whereSql = implode(' AND ', $where);
        $allowedSort = ['id', 'po_number', 'status', 'order_date', 'expected_delivery_date', 'total_amount', 'created_at'];
        $sortBy = in_array($filters->sortBy, $allowedSort, true) ? $filters->sortBy : 'created_at';
        $sortDir = $filters->sortDir === 'asc' ? 'ASC' : 'DESC';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM purchase_orders po LEFT JOIN vendors v ON v.id = po.vendor_id WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $sql = "SELECT po.*, v.name AS vendor_name, w.name AS warehouse_name, u.name AS created_by_name,
                       pr.request_number AS purchase_request_number
                FROM purchase_orders po
                LEFT JOIN vendors v ON v.id = po.vendor_id
                LEFT JOIN warehouses w ON w.id = po.warehouse_id
                LEFT JOIN users u ON u.id = po.created_by
                LEFT JOIN purchase_requests pr ON pr.id = po.purchase_request_id
                WHERE {$whereSql}
                ORDER BY po.{$sortBy} {$sortDir}
                LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(static fn(array $row) => PurchaseOrder::fromRow(array_merge($row, ['item_count' => 0])), $rows);

        if ($items !== []) {
            $ids = array_map(static fn(PurchaseOrder $o) => $o->id(), $items);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $countSql = "SELECT purchase_order_id, COUNT(*) AS cnt, SUM(quantity_ordered) AS total_ordered, SUM(quantity_received) AS total_received FROM purchase_order_items WHERE purchase_order_id IN ({$placeholders}) GROUP BY purchase_order_id";
            $countStmt2 = $this->pdo->prepare($countSql);
            $countStmt2->execute($ids);
            $counts = [];
            while ($c = $countStmt2->fetch(PDO::FETCH_ASSOC)) {
                $counts[(int) $c['purchase_order_id']] = ['cnt' => (int) $c['cnt'], 'ordered' => (float) $c['total_ordered'], 'received' => (float) $c['total_received']];
            }
            $items = array_map(static function (PurchaseOrder $o) use ($counts) {
                $arr = $o->toArray();
                $arr['item_count'] = $counts[$o->id()]['cnt'] ?? 0;
                $arr['total_ordered'] = $counts[$o->id()]['ordered'] ?? 0;
                $arr['total_received'] = $counts[$o->id()]['received'] ?? 0;
                return PurchaseOrder::fromRow($arr);
            }, $items);
        }

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => $lastPage];
    }

    public function find(int $id): ?PurchaseOrder
    {
        $stmt = $this->pdo->prepare(
            'SELECT po.*, v.name AS vendor_name, w.name AS warehouse_name, u.name AS created_by_name,
                    pr.request_number AS purchase_request_number
             FROM purchase_orders po
             LEFT JOIN vendors v ON v.id = po.vendor_id
             LEFT JOIN warehouses w ON w.id = po.warehouse_id
             LEFT JOIN users u ON u.id = po.created_by
             LEFT JOIN purchase_requests pr ON pr.id = po.purchase_request_id
             WHERE po.id = ? AND po.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? PurchaseOrder::fromRow(array_merge($row, ['items' => $this->getItems($id), 'approvals' => $this->getApprovals($id)])) : null;
    }

    public function create(PurchaseOrderData $data, int $actorId): PurchaseOrder
    {
        $number = $this->nextPoNumber();
        $now = date('Y-m-d H:i:s');
        $orderDate = $data->orderDate ?? date('Y-m-d');

        // Calculate totals from items
        $subtotal = 0.0;
        foreach ($data->items as $item) {
            $subtotal += (float) ($item['quantity_ordered'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }
        $taxAmount = $subtotal * ($data->taxRate / 100);
        $totalAmount = $subtotal + $taxAmount - $data->discountAmount;

        $stmt = $this->pdo->prepare(
            'INSERT INTO purchase_orders (po_number, vendor_id, warehouse_id, purchase_request_id, currency, tax_rate, discount_amount, subtotal, tax_amount, total_amount, order_date, expected_delivery_date, status, notes, created_by, updated_by, created_at, updated_at)
             VALUES (:num, :vid, :wid, :prid, :cur, :tax, :disc, :sub, :taxamt, :total, :odate, :edate, :status, :notes, :cb, :ub, :cat, :uat)'
        );
        $stmt->execute([
            'num' => $number,
            'vid' => $data->vendorId,
            'wid' => $data->warehouseId,
            'prid' => $data->purchaseRequestId,
            'cur' => $data->currency,
            'tax' => $data->taxRate,
            'disc' => $data->discountAmount,
            'sub' => $subtotal,
            'taxamt' => $taxAmount,
            'total' => max(0, $totalAmount),
            'odate' => $orderDate,
            'edate' => $data->expectedDeliveryDate,
            'status' => 'draft',
            'notes' => $data->notes,
            'cb' => $actorId,
            'ub' => $actorId,
            'cat' => $now,
            'uat' => $now,
        ]);
        $orderId = (int) $this->pdo->lastInsertId();

        $itemStmt = $this->pdo->prepare(
            'INSERT INTO purchase_order_items (purchase_order_id, product_id, description, quantity_ordered, unit_price, line_total, notes) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($data->items as $item) {
            $qty = (float) ($item['quantity_ordered'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $itemStmt->execute([
                $orderId,
                (int) ($item['product_id'] ?? 0),
                $item['description'] ?? null,
                $qty,
                $price,
                $qty * $price,
                $item['notes'] ?? null,
            ]);
        }

        // Mark purchase request as converted if linked
        if ($data->purchaseRequestId !== null) {
            $this->pdo->prepare('UPDATE purchase_requests SET status = ?, updated_by = ?, updated_at = ? WHERE id = ? AND status = ?')
                ->execute(['converted', $actorId, $now, $data->purchaseRequestId, 'approved']);
        }

        return $this->find($orderId) ?? PurchaseOrder::fromRow(['id' => $orderId, 'po_number' => $number, 'status' => 'draft']);
    }

    public function update(int $id, PurchaseOrderData $data, int $actorId): PurchaseOrder
    {
        $now = date('Y-m-d H:i:s');
        $orderDate = $data->orderDate ?? date('Y-m-d');

        $subtotal = 0.0;
        foreach ($data->items as $item) {
            $subtotal += (float) ($item['quantity_ordered'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }
        $taxAmount = $subtotal * ($data->taxRate / 100);
        $totalAmount = $subtotal + $taxAmount - $data->discountAmount;

        $stmt = $this->pdo->prepare(
            'UPDATE purchase_orders SET vendor_id = ?, warehouse_id = ?, currency = ?, tax_rate = ?, discount_amount = ?, subtotal = ?, tax_amount = ?, total_amount = ?, order_date = ?, expected_delivery_date = ?, notes = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $data->vendorId, $data->warehouseId, $data->currency, $data->taxRate, $data->discountAmount,
            $subtotal, $taxAmount, max(0, $totalAmount), $orderDate, $data->expectedDeliveryDate, $data->notes,
            $actorId, $now, $id,
        ]);

        // Replace items
        $this->pdo->prepare('DELETE FROM purchase_order_items WHERE purchase_order_id = ?')->execute([$id]);
        $itemStmt = $this->pdo->prepare(
            'INSERT INTO purchase_order_items (purchase_order_id, product_id, description, quantity_ordered, unit_price, line_total, notes) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($data->items as $item) {
            $qty = (float) ($item['quantity_ordered'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $itemStmt->execute([
                $id,
                (int) ($item['product_id'] ?? 0),
                $item['description'] ?? null,
                $qty,
                $price,
                $qty * $price,
                $item['notes'] ?? null,
            ]);
        }

        return $this->find($id) ?? PurchaseOrder::fromRow(['id' => $id]);
    }

    public function updateStatus(int $id, string $status, int $actorId): PurchaseOrder
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE purchase_orders SET status = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$status, $actorId, $now, $id]);
        return $this->find($id) ?? PurchaseOrder::fromRow(['id' => $id, 'status' => $status]);
    }

    public function addApproval(int $orderId, int $approverId, string $decision, ?string $comments): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO po_approvals (purchase_order_id, approver_user_id, decision, comments, decided_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$orderId, $approverId, $decision, $comments, date('Y-m-d H:i:s')]);
    }

    public function updateItemReceived(int $itemId, float $receivedDelta, float $damagedDelta): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE purchase_order_items SET quantity_received = quantity_received + ?, quantity_damaged = quantity_damaged + ? WHERE id = ?'
        );
        $stmt->execute([$receivedDelta, $damagedDelta, $itemId]);
    }

    public function nextPoNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";
        $stmt = $this->pdo->prepare("SELECT po_number FROM purchase_orders WHERE po_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        $seq = 1;
        if ($last) {
            $parts = explode('-', (string) $last);
            $seq = (int) end($parts) + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /** @return array<int, array<string, mixed>> */
    public function getApprovals(int $orderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pa.*, u.name AS approver_name FROM po_approvals pa LEFT JOIN users u ON u.id = pa.approver_user_id WHERE pa.purchase_order_id = ? ORDER BY pa.decided_at ASC'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> */
    public function getItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT poi.*, p.name AS product_name, p.sku, u.name AS unit_name, u.symbol AS unit_symbol
             FROM purchase_order_items poi
             LEFT JOIN inventory_products p ON p.id = poi.product_id
             LEFT JOIN inventory_units u ON u.id = p.unit_id
             WHERE poi.purchase_order_id = ?
             ORDER BY poi.id ASC'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recalculateTotals(int $orderId): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(line_total), 0) AS subtotal FROM purchase_order_items WHERE purchase_order_id = ?'
        );
        $stmt->execute([$orderId]);
        $subtotal = (float) $stmt->fetchColumn();

        $poStmt = $this->pdo->prepare('SELECT tax_rate, discount_amount FROM purchase_orders WHERE id = ?');
        $poStmt->execute([$orderId]);
        $po = $poStmt->fetch(PDO::FETCH_ASSOC);
        $taxRate = (float) ($po['tax_rate'] ?? 0);
        $discount = (float) ($po['discount_amount'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = max(0, $subtotal + $taxAmount - $discount);

        $this->pdo->prepare(
            'UPDATE purchase_orders SET subtotal = ?, tax_amount = ?, total_amount = ? WHERE id = ?'
        )->execute([$subtotal, $taxAmount, $total, $orderId]);
    }
}
