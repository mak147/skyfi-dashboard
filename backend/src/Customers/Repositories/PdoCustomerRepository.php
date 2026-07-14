<?php

declare(strict_types=1);

namespace SkyFi\Customers\Repositories;

use PDO;
use SkyFi\Customers\Contracts\CustomerRepositoryContract;
use SkyFi\Customers\Data\CustomerListFilters;
use SkyFi\Customers\Models\Customer;

final class PdoCustomerRepository implements CustomerRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?Customer
    {
        $statement = $this->pdo->prepare('SELECT * FROM customers WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : Customer::fromRow($row);
    }

    public function findActive(int $id): ?Customer
    {
        $statement = $this->pdo->prepare('SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : Customer::fromRow($row);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM customers WHERE customer_code = :code';
        $params = ['code' => $code];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function cnicExists(string $cnic, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM customers WHERE cnic = :cnic';
        $params = ['cnic' => $cnic];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function list(CustomerListFilters $filters): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if ($filters->status !== null) {
            $where[] = 'status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->city !== null) {
            $where[] = 'city = :city';
            $params['city'] = $filters->city;
        }

        if ($filters->area !== null) {
            $where[] = 'area = :area';
            $params['area'] = $filters->area;
        }

        if ($filters->search !== null) {
            $where[] = '(full_name LIKE :search OR phone LIKE :search OR email LIKE :search OR customer_code LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sortColumn = 'created_at';
        $sortDirection = 'DESC';

        $rawSort = ltrim($filters->sort, '-');
        $allowedSorts = ['created_at', 'updated_at', 'full_name', 'status', 'city', 'area'];
        if (in_array($rawSort, $allowedSorts, true)) {
            $sortColumn = $rawSort;
        }
        if (str_starts_with($filters->sort, '-')) {
            $sortDirection = 'DESC';
        } else {
            $sortDirection = 'ASC';
        }

        $countStatement = $this->pdo->prepare("SELECT COUNT(*) FROM customers {$whereClause}");
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;

        $sql = "SELECT * FROM customers {$whereClause} ORDER BY {$sortColumn} {$sortDirection} LIMIT :limit OFFSET :offset";
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $items = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $items[] = Customer::fromRow($row);
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

    public function create(array $data): Customer
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $col): string => ":{$col}", $columns);

        $sql = 'INSERT INTO customers (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        $id = (int) $this->pdo->lastInsertId();

        $customer = $this->find($id);
        if ($customer === null) {
            throw new \RuntimeException('Failed to retrieve created customer.');
        }

        return $customer;
    }

    public function update(int $id, array $data): Customer
    {
        $sets = array_map(static fn (string $col): string => "{$col} = :{$col}", array_keys($data));
        $sql = 'UPDATE customers SET ' . implode(', ', $sets) . ' WHERE id = :id';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_merge($data, ['id' => $id]));

        $customer = $this->findActive($id);
        if ($customer === null) {
            throw new \RuntimeException('Failed to retrieve updated customer.');
        }

        return $customer;
    }

    public function softDelete(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE customers SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['id' => $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE customers SET status = :status WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['status' => $status, 'id' => $id]);
    }
}
