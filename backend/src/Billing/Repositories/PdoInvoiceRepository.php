<?php

declare(strict_types=1);

namespace SkyFi\Billing\Repositories;

use PDO;
use SkyFi\Billing\Contracts\InvoiceRepositoryContract;
use SkyFi\Billing\Data\InvoiceListFilters;
use SkyFi\Billing\Models\Invoice;
use SkyFi\Billing\Models\InvoiceItem;

final class PdoInvoiceRepository implements InvoiceRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?Invoice
    {
        $statement = $this->pdo->prepare('SELECT i.*, c.full_name as customer_name, c.customer_code, conn.connection_number, p.name as package_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id LEFT JOIN connections conn ON i.connection_id = conn.id LEFT JOIN packages p ON i.package_id = p.id WHERE i.id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : Invoice::fromRow($row, $this->getItems($id), $this->getActivities($id));
    }

    public function findActive(int $id): ?Invoice
    {
        $statement = $this->pdo->prepare('SELECT i.*, c.full_name as customer_name, c.customer_code, conn.connection_number, p.name as package_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id LEFT JOIN connections conn ON i.connection_id = conn.id LEFT JOIN packages p ON i.package_id = p.id WHERE i.id = :id AND i.deleted_at IS NULL');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : Invoice::fromRow($row, $this->getItems($id), $this->getActivities($id));
    }

    public function list(InvoiceListFilters $filters): array
    {
        $where = ['i.deleted_at IS NULL'];
        $params = [];

        if ($filters->status !== null) {
            $where[] = 'i.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->customerId !== null) {
            $where[] = 'i.customer_id = :customer_id';
            $params['customer_id'] = $filters->customerId;
        }

        if ($filters->dueFrom !== null) {
            $where[] = 'i.due_date >= :due_from';
            $params['due_from'] = $filters->dueFrom;
        }

        if ($filters->dueTo !== null) {
            $where[] = 'i.due_date <= :due_to';
            $params['due_to'] = $filters->dueTo;
        }

        if ($filters->search !== null) {
            $where[] = '(i.invoice_number LIKE :search OR c.full_name LIKE :search OR c.customer_code LIKE :search OR conn.connection_number LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sortColumn = 'i.created_at';
        $sortDirection = 'DESC';
        $rawSort = ltrim($filters->sort, '-');
        $allowedSorts = ['created_at', 'updated_at', 'issue_date', 'due_date', 'total_amount', 'status'];
        if (in_array($rawSort, $allowedSorts, true)) {
            $sortColumn = 'i.' . $rawSort;
        }
        if (str_starts_with($filters->sort, '-')) {
            $sortDirection = 'DESC';
        } else {
            $sortDirection = 'ASC';
        }

        $countStatement = $this->pdo->prepare("SELECT COUNT(*) FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id LEFT JOIN connections conn ON i.connection_id = conn.id {$whereClause}");
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;

        $sql = "SELECT i.*, c.full_name as customer_name, c.customer_code, conn.connection_number, p.name as package_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id LEFT JOIN connections conn ON i.connection_id = conn.id LEFT JOIN packages p ON i.package_id = p.id {$whereClause} ORDER BY {$sortColumn} {$sortDirection} LIMIT :limit OFFSET :offset";
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $items = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $items[] = Invoice::fromRow($row);
        }

        $lastPage = (int) max(1, ceil($total / $filters->perPage));

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function create(array $data): Invoice
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $col): string => ":{$col}", $columns);

        $sql = 'INSERT INTO invoices (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        $id = (int) $this->pdo->lastInsertId();
        $invoice = $this->find($id);
        if ($invoice === null) {
            throw new \RuntimeException('Failed to retrieve created invoice.');
        }

        return $invoice;
    }

    public function update(int $id, array $data): Invoice
    {
        $sets = array_map(static fn(string $col): string => "{$col} = :{$col}", array_keys($data));
        $sql = 'UPDATE invoices SET ' . implode(', ', $sets) . ' WHERE id = :id AND deleted_at IS NULL';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_merge($data, ['id' => $id]));

        $invoice = $this->findActive($id);
        if ($invoice === null) {
            throw new \RuntimeException('Failed to retrieve updated invoice.');
        }

        return $invoice;
    }

    public function softDelete(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE invoices SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE invoices SET status = :status WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['status' => $status, 'id' => $id]);
    }

    public function addItems(int $invoiceId, array $items): void
    {
        if ($items === []) {
            return;
        }

        $sql = 'INSERT INTO invoice_items (invoice_id, item_type, description, quantity, unit_price, amount, tax_amount, discount_amount) VALUES ';
        $values = [];
        $params = [];

        foreach ($items as $index => $item) {
            $values[] = "(:invoice_id_{$index}, :item_type_{$index}, :description_{$index}, :quantity_{$index}, :unit_price_{$index}, :amount_{$index}, :tax_amount_{$index}, :discount_amount_{$index})";
            $params["invoice_id_{$index}"] = $invoiceId;
            $params["item_type_{$index}"] = $item['item_type'];
            $params["description_{$index}"] = $item['description'];
            $params["quantity_{$index}"] = $item['quantity'];
            $params["unit_price_{$index}"] = $item['unit_price'];
            $params["amount_{$index}"] = $item['amount'];
            $params["tax_amount_{$index}"] = $item['tax_amount'];
            $params["discount_amount_{$index}"] = $item['discount_amount'];
        }

        $sql .= implode(', ', $values);
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
    }

    public function getItems(int $invoiceId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY id ASC');
        $statement->execute(['invoice_id' => $invoiceId]);

        $items = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $items[] = InvoiceItem::fromRow($row);
        }

        return $items;
    }

    public function deleteItems(int $invoiceId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = :invoice_id');
        $statement->execute(['invoice_id' => $invoiceId]);
    }

    public function addActivity(int $invoiceId, string $action, ?string $description, ?int $performedBy): void
    {
        $statement = $this->pdo->prepare('INSERT INTO invoice_activities (invoice_id, action, description, performed_by) VALUES (:invoice_id, :action, :description, :performed_by)');
        $statement->execute([
            'invoice_id' => $invoiceId,
            'action' => $action,
            'description' => $description,
            'performed_by' => $performedBy,
        ]);
    }

    public function getActivities(int $invoiceId): array
    {
        $statement = $this->pdo->prepare('SELECT ia.*, u.name as performed_by_name FROM invoice_activities ia LEFT JOIN users u ON ia.performed_by = u.id WHERE ia.invoice_id = :invoice_id ORDER BY ia.created_at DESC');
        $statement->execute(['invoice_id' => $invoiceId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function numberExists(string $number, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM invoices WHERE invoice_number = :number';
        $params = ['number' => $number];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function statistics(): array
    {
        $today = date('Y-m-d');
        $firstOfMonth = date('Y-m-01');

        $sql = "SELECT 
            (SELECT COUNT(*) FROM invoices WHERE deleted_at IS NULL AND DATE(created_at) = :today) as invoices_today,
            (SELECT COUNT(*) FROM invoices WHERE deleted_at IS NULL AND created_at >= :first_of_month) as invoices_this_month,
            (SELECT COUNT(*) FROM invoices WHERE deleted_at IS NULL AND status IN ('pending','issued')) as pending_invoices,
            (SELECT COUNT(*) FROM invoices WHERE deleted_at IS NULL AND status = 'paid') as paid_invoices,
            (SELECT COUNT(*) FROM invoices WHERE deleted_at IS NULL AND status = 'overdue') as overdue_invoices";

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['today' => $today, 'first_of_month' => $firstOfMonth]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return array_map('intval', $row);
    }
}
