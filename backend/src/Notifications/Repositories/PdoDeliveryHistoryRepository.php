<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Repositories;

use PDO;
use SkyFi\Notifications\Contracts\DeliveryHistoryRepositoryContract;
use SkyFi\Notifications\DomainModels\DeliveryHistory;
use SkyFi\Notifications\DTOs\DeliveryListFilters;

final class PdoDeliveryHistoryRepository implements DeliveryHistoryRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(DeliveryListFilters $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if ($filters->channel !== null) {
            $where[] = 'channel = :channel';
            $params['channel'] = $filters->channel;
        }
        if ($filters->status !== null) {
            $where[] = 'status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->recipientUserId !== null) {
            $where[] = 'recipient_user_id = :recipient_user_id';
            $params['recipient_user_id'] = $filters->recipientUserId;
        }
        if ($filters->search !== null) {
            $where[] = '(subject LIKE :search OR body LIKE :search OR fail_reason LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM notification_deliveries WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM notification_deliveries WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(
                static fn (array $row): DeliveryHistory => DeliveryHistory::fromRow($row),
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            ),
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / $filters->perPage)),
        ];
    }

    public function find(int $id): ?DeliveryHistory
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notification_deliveries WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? DeliveryHistory::fromRow($row) : null;
    }

    public function create(array $data): DeliveryHistory
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO notification_deliveries (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($data);
        $id = (int) $this->pdo->lastInsertId();
        $found = $this->find($id);

        return $found ?? DeliveryHistory::fromRow(['id' => $id] + $data);
    }

    public function update(int $id, array $data): DeliveryHistory
    {
        if ($data !== []) {
            $sets = array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($data));
            $stmt = $this->pdo->prepare(
                'UPDATE notification_deliveries SET ' . implode(',', $sets) . ' WHERE id = :id'
            );
            $stmt->execute($data + ['id' => $id]);
        }
        $found = $this->find($id);

        return $found ?? DeliveryHistory::fromRow(['id' => $id] + $data);
    }
}
