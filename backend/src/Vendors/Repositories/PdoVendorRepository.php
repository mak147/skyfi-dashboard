<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Repositories;

use PDO;
use SkyFi\Vendors\Contracts\VendorRepositoryContract;
use SkyFi\Vendors\DomainModels\Vendor;
use SkyFi\Vendors\DTOs\VendorData;
use SkyFi\Vendors\DTOs\VendorListFilters;

final class PdoVendorRepository implements VendorRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(VendorListFilters $filters): array
    {
        $where = ['v.deleted_at IS NULL'];
        $params = [];

        if ($filters->search !== null && $filters->search !== '') {
            $where[] = '(v.code LIKE :search OR v.name LIKE :search2 OR v.email LIKE :search3 OR v.contact_name LIKE :search4)';
            $params['search'] = $params['search2'] = $params['search3'] = $params['search4'] = '%' . $filters->search . '%';
        }
        if ($filters->status !== null && $filters->status !== '') {
            $where[] = 'v.status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->category !== null && $filters->category !== '') {
            $where[] = 'v.category = :category';
            $params['category'] = $filters->category;
        }
        if ($filters->minRating !== null) {
            $where[] = 'v.overall_rating >= :min_rating';
            $params['min_rating'] = $filters->minRating;
        }

        $whereSql = implode(' AND ', $where);
        $allowedSort = ['id', 'code', 'name', 'status', 'category', 'overall_rating', 'created_at'];
        $sortBy = in_array($filters->sortBy, $allowedSort, true) ? $filters->sortBy : 'name';
        $sortDir = $filters->sortDir === 'desc' ? 'DESC' : 'ASC';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM vendors v WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $sql = "SELECT v.*, u.name AS created_by_name
                FROM vendors v
                LEFT JOIN users u ON u.id = v.created_by
                WHERE {$whereSql}
                ORDER BY v.{$sortBy} {$sortDir}
                LIMIT {$filters->perPage} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = array_map(static fn(array $row) => Vendor::fromRow($row), $rows);
        $lastPage = max(1, (int) ceil($total / $filters->perPage));

        return ['items' => $items, 'total' => $total, 'page' => $filters->page, 'perPage' => $filters->perPage, 'lastPage' => $lastPage];
    }

    public function find(int $id): ?Vendor
    {
        $stmt = $this->pdo->prepare(
            'SELECT v.*, u.name AS created_by_name
             FROM vendors v
             LEFT JOIN users u ON u.id = v.created_by
             WHERE v.id = ? AND v.deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Fetch contacts counts and summary
        $contactStmt = $this->pdo->prepare('SELECT COUNT(*) FROM vendor_contacts WHERE vendor_id = ? AND deleted_at IS NULL');
        $contactStmt->execute([$id]);
        $row['contacts_count'] = (int) $contactStmt->fetchColumn();

        $contractStmt = $this->pdo->prepare('SELECT COUNT(*) FROM vendor_contracts WHERE vendor_id = ? AND deleted_at IS NULL');
        $contractStmt->execute([$id]);
        $row['contracts_count'] = (int) $contractStmt->fetchColumn();

        return Vendor::fromRow($row);
    }

    public function create(VendorData $data, int $actorId): Vendor
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO vendors (code, name, status, contact_name, email, phone, website, tax_id, registration_number, address, city, country, payment_terms, currency, category, notes, created_by, updated_by, created_at, updated_at)
             VALUES (:code, :name, :status, :contact_name, :email, :phone, :website, :tax_id, :reg_num, :address, :city, :country, :payment_terms, :currency, :category, :notes, :cb, :ub, :cat, :uat)'
        );
        $stmt->execute([
            'code' => $data->code,
            'name' => $data->name,
            'status' => $data->status,
            'contact_name' => $data->contactName,
            'email' => $data->email,
            'phone' => $data->phone,
            'website' => $data->website,
            'tax_id' => $data->taxId,
            'reg_num' => $data->registrationNumber,
            'address' => $data->address,
            'city' => $data->city,
            'country' => $data->country,
            'payment_terms' => $data->paymentTerms,
            'currency' => $data->currency,
            'category' => $data->category,
            'notes' => $data->notes,
            'cb' => $actorId,
            'ub' => $actorId,
            'cat' => $now,
            'uat' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? throw new \RuntimeException('Failed to load created vendor.');
    }

    public function update(int $id, VendorData $data, int $actorId): Vendor
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'UPDATE vendors SET code = :code, name = :name, status = :status, contact_name = :contact_name, email = :email, phone = :phone, website = :website, tax_id = :tax_id, registration_number = :reg_num, address = :address, city = :city, country = :country, payment_terms = :payment_terms, currency = :currency, category = :category, notes = :notes, updated_by = :ub, updated_at = :uat WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'code' => $data->code,
            'name' => $data->name,
            'status' => $data->status,
            'contact_name' => $data->contactName,
            'email' => $data->email,
            'phone' => $data->phone,
            'website' => $data->website,
            'tax_id' => $data->taxId,
            'reg_num' => $data->registrationNumber,
            'address' => $data->address,
            'city' => $data->city,
            'country' => $data->country,
            'payment_terms' => $data->paymentTerms,
            'currency' => $data->currency,
            'category' => $data->category,
            'notes' => $data->notes,
            'ub' => $actorId,
            'uat' => $now,
            'id' => $id,
        ]);
        return $this->find($id) ?? throw new \RuntimeException('Vendor not found.');
    }

    public function updateStatus(int $id, string $status, int $actorId): Vendor
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE vendors SET status = ?, updated_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$status, $actorId, $now, $id]);
        return $this->find($id) ?? throw new \RuntimeException('Vendor not found.');
    }

    public function getPurchasingHistory(int $id): array
    {
        // Purchase Orders
        $poStmt = $this->pdo->prepare(
            'SELECT id, po_number, order_date, status, total_amount, currency
             FROM purchase_orders
             WHERE vendor_id = ? AND deleted_at IS NULL
             ORDER BY order_date DESC'
        );
        $poStmt->execute([$id]);
        $orders = $poStmt->fetchAll(PDO::FETCH_ASSOC);

        // Supplier Invoices
        $invStmt = $this->pdo->prepare(
            'SELECT id, invoice_number, invoice_date, status, total_amount, currency
             FROM supplier_invoices
             WHERE vendor_id = ? AND deleted_at IS NULL
             ORDER BY invoice_date DESC'
        );
        $invStmt->execute([$id]);
        $invoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

        // Inventory Catalog Products supplied by vendor
        $prodStmt = $this->pdo->prepare(
            'SELECT p.id, p.sku, p.name, pv.vendor_sku, pv.last_purchase_cost, pv.lead_time_days
             FROM inventory_product_vendors pv
             JOIN inventory_products p ON p.id = pv.product_id
             WHERE pv.vendor_id = ? AND p.deleted_at IS NULL
             ORDER BY p.name ASC'
        );
        $prodStmt->execute([$id]);
        $products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total spend
        $spendStmt = $this->pdo->prepare(
            "SELECT SUM(total_amount) FROM purchase_orders WHERE vendor_id = ? AND status IN ('approved', 'sent', 'partially_received', 'fully_received', 'closed') AND deleted_at IS NULL"
        );
        $spendStmt->execute([$id]);
        $totalSpend = (float) ($spendStmt->fetchColumn() ?? 0.0);

        return [
            'purchase_orders' => $orders,
            'supplier_invoices' => $invoices,
            'catalog_products' => $products,
            'total_procurement_spend' => $totalSpend,
        ];
    }
}
