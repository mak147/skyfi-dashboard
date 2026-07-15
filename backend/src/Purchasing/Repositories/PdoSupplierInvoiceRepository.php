<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Repositories;

use PDO;
use SkyFi\Purchasing\Contracts\SupplierInvoiceRepositoryContract;
use SkyFi\Purchasing\DomainModels\SupplierInvoice;
use SkyFi\Purchasing\DTOs\SupplierInvoiceData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;

final class PdoSupplierInvoiceRepository implements SupplierInvoiceRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(PurchaseOrderListFilters $filters): array
    {
        $where = ['si.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null && $filters->search !== '') {
            $where[] = '(si.invoice_number LIKE :search OR si.notes LIKE :search2 OR v.name LIKE :search3)';
            $params['search'] = $params['search2'] = $params['search3'] = '%' . $filters->search . '%';
        }
        if ($filters->status !== null) {
            $where[] = 'si.status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->vendorId !== null) {
            $where[] = 'si.vendor_id = :vendor_id';
            $params['vendor_id'] = $filters->vendorId;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM supplier_invoices si LEFT JOIN vendors v ON v.id = si.vendor_id WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $sql = "SELECT si.*, v.name AS vendor_name, po.po_number, u.name AS created_by_name
                FROM supplier_invoices si
                LEFT JOIN vendors v ON v.id = si.vendor_id
                LEFT JOIN purchase_orders po ON po.id = si.purchase_order_id
                LEFT JOIN users u ON u.id = si.created_by
                WHERE {$whereSql}
                ORDER BY si.invoice_date DESC
                LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(static fn(array $row) => SupplierInvoice::fromRow($row), $rows);
        $lastPage = max(1, (int) ceil($total / $filters->perPage));
        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => $lastPage];
    }

    public function find(int $id): ?SupplierInvoice
    {
        $stmt = $this->pdo->prepare(
            'SELECT si.*, v.name AS vendor_name, po.po_number, u.name AS created_by_name
             FROM supplier_invoices si
             LEFT JOIN vendors v ON v.id = si.vendor_id
             LEFT JOIN purchase_orders po ON po.id = si.purchase_order_id
             LEFT JOIN users u ON u.id = si.created_by
             WHERE si.id = ? AND si.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? SupplierInvoice::fromRow($row) : null;
    }

    public function create(SupplierInvoiceData $data, int $actorId): SupplierInvoice
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO supplier_invoices (invoice_number, vendor_id, purchase_order_id, invoice_date, due_date, subtotal, tax_amount, total_amount, currency, status, notes, created_by, updated_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data->invoiceNumber, $data->vendorId, $data->purchaseOrderId, $data->invoiceDate,
            $data->dueDate, $data->subtotal, $data->taxAmount, $data->totalAmount, $data->currency,
            'registered', $data->notes, $actorId, $actorId, $now, $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? SupplierInvoice::fromRow(['id' => $id, 'invoice_number' => $data->invoiceNumber, 'status' => 'registered']);
    }

    public function update(int $id, SupplierInvoiceData $data, int $actorId): SupplierInvoice
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE supplier_invoices SET invoice_number = ?, vendor_id = ?, purchase_order_id = ?, invoice_date = ?, due_date = ?, subtotal = ?, tax_amount = ?, total_amount = ?, currency = ?, notes = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([
            $data->invoiceNumber, $data->vendorId, $data->purchaseOrderId, $data->invoiceDate,
            $data->dueDate, $data->subtotal, $data->taxAmount, $data->totalAmount, $data->currency,
            $data->notes, $actorId, $now, $id,
        ]);
        return $this->find($id) ?? SupplierInvoice::fromRow(['id' => $id]);
    }
}
