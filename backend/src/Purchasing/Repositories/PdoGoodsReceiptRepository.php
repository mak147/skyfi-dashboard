<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Repositories;

use PDO;
use SkyFi\Purchasing\Contracts\GoodsReceiptRepositoryContract;
use SkyFi\Purchasing\DomainModels\GoodsReceipt;
use SkyFi\Purchasing\DTOs\GoodsReceiptData;
use SkyFi\Purchasing\DTOs\GoodsReceiptListFilters;

final class PdoGoodsReceiptRepository implements GoodsReceiptRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(GoodsReceiptListFilters $filters): array
    {
        $where = ['gr.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null && $filters->search !== '') {
            $where[] = '(gr.receipt_number LIKE :search OR gr.notes LIKE :search2)';
            $params['search'] = $params['search2'] = '%' . $filters->search . '%';
        }
        if ($filters->status !== null) {
            $where[] = 'gr.status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->purchaseOrderId !== null) {
            $where[] = 'gr.purchase_order_id = :po_id';
            $params['po_id'] = $filters->purchaseOrderId;
        }

        $whereSql = implode(' AND ', $where);
        $allowedSort = ['id', 'receipt_number', 'status', 'received_at', 'created_at'];
        $sortBy = in_array($filters->sortBy, $allowedSort, true) ? $filters->sortBy : 'received_at';
        $sortDir = $filters->sortDir === 'asc' ? 'ASC' : 'DESC';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM goods_receipts gr WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $sql = "SELECT gr.*, po.po_number, v.name AS vendor_name, w.name AS warehouse_name,
                       u.name AS received_by_name
                FROM goods_receipts gr
                LEFT JOIN purchase_orders po ON po.id = gr.purchase_order_id
                LEFT JOIN vendors v ON v.id = po.vendor_id
                LEFT JOIN warehouses w ON w.id = gr.warehouse_id
                LEFT JOIN users u ON u.id = gr.received_by
                WHERE {$whereSql}
                ORDER BY gr.{$sortBy} {$sortDir}
                LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(static fn(array $row) => GoodsReceipt::fromRow($row), $rows);

        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => $lastPage];
    }

    public function find(int $id): ?GoodsReceipt
    {
        $stmt = $this->pdo->prepare(
            'SELECT gr.*, po.po_number, v.name AS vendor_name, w.name AS warehouse_name, u.name AS received_by_name
             FROM goods_receipts gr
             LEFT JOIN purchase_orders po ON po.id = gr.purchase_order_id
             LEFT JOIN vendors v ON v.id = po.vendor_id
             LEFT JOIN warehouses w ON w.id = gr.warehouse_id
             LEFT JOIN users u ON u.id = gr.received_by
             WHERE gr.id = ? AND gr.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? GoodsReceipt::fromRow(array_merge($row, ['items' => $this->getItems($id)])) : null;
    }

    public function create(GoodsReceiptData $data, int $actorId): GoodsReceipt
    {
        $number = $this->nextReceiptNumber();
        $now = date('Y-m-d H:i:s');

        // Determine status based on whether all items are fully received
        $hasShort = false;
        foreach ($data->items as $item) {
            if ((float) ($item['quantity_short'] ?? 0) > 0) {
                $hasShort = true;
                break;
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO goods_receipts (receipt_number, purchase_order_id, warehouse_id, status, received_by, received_at, notes, created_at, updated_at)
             VALUES (:num, :poid, :wid, :status, :rb, :rat, :notes, :cat, :uat)'
        );
        $stmt->execute([
            'num' => $number,
            'poid' => $data->purchaseOrderId,
            'wid' => $data->warehouseId,
            'status' => $hasShort ? 'partial' : 'received',
            'rb' => $actorId,
            'rat' => $now,
            'notes' => $data->notes,
            'cat' => $now,
            'uat' => $now,
        ]);
        $receiptId = (int) $this->pdo->lastInsertId();

        $itemStmt = $this->pdo->prepare(
            'INSERT INTO goods_receipt_items (goods_receipt_id, purchase_order_item_id, product_id, quantity_accepted, quantity_damaged, quantity_short, warehouse_location_id, `condition`, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($data->items as $item) {
            $itemStmt->execute([
                $receiptId,
                (int) ($item['purchase_order_item_id'] ?? 0),
                (int) ($item['product_id'] ?? 0),
                (float) ($item['quantity_accepted'] ?? 0),
                (float) ($item['quantity_damaged'] ?? 0),
                (float) ($item['quantity_short'] ?? 0),
                isset($item['warehouse_location_id']) ? (int) $item['warehouse_location_id'] : null,
                $item['condition'] ?? 'available',
                $item['notes'] ?? null,
            ]);
        }

        return $this->find($receiptId) ?? GoodsReceipt::fromRow(['id' => $receiptId, 'receipt_number' => $number]);
    }

    public function nextReceiptNumber(): string
    {
        $year = date('Y');
        $prefix = "GR-{$year}-";
        $stmt = $this->pdo->prepare("SELECT receipt_number FROM goods_receipts WHERE receipt_number LIKE ? ORDER BY id DESC LIMIT 1");
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
    public function getItems(int $receiptId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT gri.*, p.name AS product_name, p.sku, u.name AS unit_name, u.symbol AS unit_symbol,
                    wl.code AS location_code, wl.name AS location_name
             FROM goods_receipt_items gri
             LEFT JOIN inventory_products p ON p.id = gri.product_id
             LEFT JOIN inventory_units u ON u.id = p.unit_id
             LEFT JOIN warehouse_locations wl ON wl.id = gri.warehouse_location_id
             WHERE gri.goods_receipt_id = ?
             ORDER BY gri.id ASC'
        );
        $stmt->execute([$receiptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markReturned(int $receiptId): GoodsReceipt
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('UPDATE goods_receipts SET status = ?, updated_at = ? WHERE id = ?')
            ->execute(['returned', $now, $receiptId]);
        return $this->find($receiptId) ?? GoodsReceipt::fromRow(['id' => $receiptId, 'status' => 'returned']);
    }
}
