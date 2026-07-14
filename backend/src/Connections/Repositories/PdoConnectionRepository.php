<?php

declare(strict_types=1);

namespace SkyFi\Connections\Repositories;

use PDO;
use SkyFi\Connections\Contracts\ConnectionRepositoryContract;
use SkyFi\Connections\Data\ConnectionListFilters;
use SkyFi\Connections\Models\Connection;

final class PdoConnectionRepository implements ConnectionRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(ConnectionListFilters $filters): array
    {
        $where = ['c.deleted_at IS NULL'];
        $params = [];

        if ($filters->status) {
            $where[] = 'c.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->type) {
            $where[] = 'c.type = :type';
            $params['type'] = $filters->type;
        }

        if ($filters->customerId) {
            $where[] = 'c.customer_id = :customer_id';
            $params['customer_id'] = $filters->customerId;
        }

        if ($filters->packageId) {
            $where[] = 'c.package_id = :package_id';
            $params['package_id'] = $filters->packageId;
        }

        if ($filters->search) {
            $where[] = '(c.connection_number LIKE :search OR c.name LIKE :search OR c.pppoe_username LIKE :search OR cust.full_name LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);
        
        $countSql = "SELECT COUNT(*) FROM connections c LEFT JOIN customers cust ON c.customer_id = cust.id WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $lastPage = (int) ceil($total / $filters->perPage);
        $offset = ($filters->page - 1) * $filters->perPage;

        $sortField = ltrim($filters->sort, '-');
        $sortOrder = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';
        
        $allowedSorts = ['created_at', 'connection_number', 'name', 'status', 'type'];
        if (!in_array($sortField, $allowedSorts, true)) {
            $sortField = 'created_at';
        }

        $sql = "SELECT c.*, cust.full_name as customer_name, p.name as package_name 
                FROM connections c 
                JOIN customers cust ON c.customer_id = cust.id 
                JOIN packages p ON c.package_id = p.id
                WHERE {$whereSql} 
                ORDER BY c.{$sortField} {$sortOrder} 
                LIMIT {$filters->perPage} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => array_map(fn($row) => Connection::fromRow($row), $rows),
            'total' => $total,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function find(int $id): ?Connection
    {
        $sql = "SELECT c.*, cust.full_name as customer_name, p.name as package_name 
                FROM connections c 
                JOIN customers cust ON c.customer_id = cust.id 
                JOIN packages p ON c.package_id = p.id
                WHERE c.id = :id AND c.deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Connection::fromRow($row) : null;
    }

    public function findByNumber(string $number): ?Connection
    {
        $sql = "SELECT * FROM connections WHERE connection_number = :number AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['number' => $number]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Connection::fromRow($row) : null;
    }

    public function existsByPppoeUsername(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM connections WHERE pppoe_username = :username AND deleted_at IS NULL";
        $params = ['username' => $username];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): Connection
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
        
        $sql = "INSERT INTO connections ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): Connection
    {
        $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($data)));
        $sql = "UPDATE connections SET {$sets} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, ['id' => $id]));
        
        return $this->find($id);
    }

    public function updateStatus(int $id, string $status): void
    {
        $sql = "UPDATE connections SET status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function softDelete(int $id): void
    {
        $sql = "UPDATE connections SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }
}
