<?php

declare(strict_types=1);

namespace SkyFi\Billing\Repositories;

use PDO;
use SkyFi\Billing\Contracts\BillingScheduleRepositoryContract;
use SkyFi\Billing\Models\BillingSchedule;

final class PdoBillingScheduleRepository implements BillingScheduleRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id): ?BillingSchedule
    {
        $statement = $this->pdo->prepare('SELECT bs.*, c.connection_number, cust.full_name as customer_name FROM billing_schedules bs LEFT JOIN connections c ON bs.connection_id = c.id LEFT JOIN customers cust ON c.customer_id = cust.id WHERE bs.id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : BillingSchedule::fromRow($row);
    }

    public function findByConnection(int $connectionId): ?BillingSchedule
    {
        $statement = $this->pdo->prepare('SELECT bs.*, c.connection_number, cust.full_name as customer_name FROM billing_schedules bs LEFT JOIN connections c ON bs.connection_id = c.id LEFT JOIN customers cust ON c.customer_id = cust.id WHERE bs.connection_id = :connection_id');
        $statement->execute(['connection_id' => $connectionId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : BillingSchedule::fromRow($row);
    }

    public function findDue(string $date, ?array $connectionIds = null): array
    {
        $sql = 'SELECT bs.*, c.connection_number, cust.full_name as customer_name, c.customer_id, c.package_id FROM billing_schedules bs LEFT JOIN connections c ON bs.connection_id = c.id LEFT JOIN customers cust ON c.customer_id = cust.id WHERE bs.next_bill_date <= :date AND bs.auto_generate = 1 AND c.status = :connection_status';
        $params = ['date' => $date, 'connection_status' => 'active'];

        if ($connectionIds !== null && $connectionIds !== []) {
            $placeholders = implode(', ', array_map(static fn(int $i): string => ":conn_{$i}", array_keys($connectionIds)));
            $sql .= " AND bs.connection_id IN ({$placeholders})";
            foreach ($connectionIds as $index => $id) {
                $params["conn_{$index}"] = $id;
            }
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $items = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $items[] = BillingSchedule::fromRow($row);
        }

        return $items;
    }

    public function list(int $page, int $perPage, string $sort): array
    {
        $sortColumn = 'bs.created_at';
        $sortDirection = 'DESC';
        $rawSort = ltrim($sort, '-');
        $allowedSorts = ['created_at', 'updated_at', 'next_bill_date', 'anchor_date'];
        if (in_array($rawSort, $allowedSorts, true)) {
            $sortColumn = 'bs.' . $rawSort;
        }
        if (str_starts_with($sort, '-')) {
            $sortDirection = 'DESC';
        } else {
            $sortDirection = 'ASC';
        }

        $countStatement = $this->pdo->prepare('SELECT COUNT(*) FROM billing_schedules bs');
        $countStatement->execute();
        $total = (int) $countStatement->fetchColumn();

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT bs.*, c.connection_number, cust.full_name as customer_name FROM billing_schedules bs LEFT JOIN connections c ON bs.connection_id = c.id LEFT JOIN customers cust ON c.customer_id = cust.id ORDER BY {$sortColumn} {$sortDirection} LIMIT :limit OFFSET :offset";
        $statement = $this->pdo->prepare($sql);
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        $items = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $items[] = BillingSchedule::fromRow($row);
        }

        $lastPage = (int) max(1, ceil($total / $perPage));

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public function create(array $data): BillingSchedule
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $col): string => ":{$col}", $columns);

        $sql = 'INSERT INTO billing_schedules (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        $id = (int) $this->pdo->lastInsertId();
        $schedule = $this->find($id);
        if ($schedule === null) {
            throw new \RuntimeException('Failed to retrieve created billing schedule.');
        }

        return $schedule;
    }

    public function update(int $id, array $data): BillingSchedule
    {
        $sets = array_map(static fn(string $col): string => "{$col} = :{$col}", array_keys($data));
        $sql = 'UPDATE billing_schedules SET ' . implode(', ', $sets) . ' WHERE id = :id';

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_merge($data, ['id' => $id]));

        $schedule = $this->find($id);
        if ($schedule === null) {
            throw new \RuntimeException('Failed to retrieve updated billing schedule.');
        }

        return $schedule;
    }

    public function updateNextBillDate(int $connectionId, string $nextBillDate): void
    {
        $statement = $this->pdo->prepare('UPDATE billing_schedules SET next_bill_date = :next_bill_date WHERE connection_id = :connection_id');
        $statement->execute(['next_bill_date' => $nextBillDate, 'connection_id' => $connectionId]);
    }
}
