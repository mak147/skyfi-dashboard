<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Repositories;

use PDO;
use SkyFi\Notifications\Contracts\NotificationEventRepositoryContract;
use SkyFi\Notifications\DomainModels\NotificationEvent;

final class PdoNotificationEventRepository implements NotificationEventRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];
        if (($filters['event_key'] ?? '') !== '') {
            $where[] = 'event_key = :event_key';
            $params['event_key'] = $filters['event_key'];
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['source_module'] ?? '') !== '') {
            $where[] = 'source_module = :source_module';
            $params['source_module'] = $filters['source_module'];
        }

        $page = max(1, (int) ($filters['page']['number'] ?? $filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['page']['size'] ?? $filters['per_page'] ?? 25)));
        $whereSql = implode(' AND ', $where);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM notification_events WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM notification_events WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(
                static fn (array $row): NotificationEvent => NotificationEvent::fromRow($row),
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            ),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function find(int $id): ?NotificationEvent
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notification_events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? NotificationEvent::fromRow($row) : null;
    }

    public function findByUuid(string $uuid): ?NotificationEvent
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notification_events WHERE event_uuid = :uuid LIMIT 1');
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? NotificationEvent::fromRow($row) : null;
    }

    public function create(array $data): NotificationEvent
    {
        if (isset($data['payload']) && is_array($data['payload'])) {
            $data['payload'] = json_encode($data['payload'], JSON_THROW_ON_ERROR);
        }
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO notification_events (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($data);
        $id = (int) $this->pdo->lastInsertId();
        $found = $this->find($id);

        return $found ?? NotificationEvent::fromRow(['id' => $id] + $data);
    }

    public function update(int $id, array $data): NotificationEvent
    {
        if (isset($data['payload']) && is_array($data['payload'])) {
            $data['payload'] = json_encode($data['payload'], JSON_THROW_ON_ERROR);
        }
        if ($data !== []) {
            $sets = array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($data));
            $stmt = $this->pdo->prepare(
                'UPDATE notification_events SET ' . implode(',', $sets) . ' WHERE id = :id'
            );
            $stmt->execute($data + ['id' => $id]);
        }
        $found = $this->find($id);

        return $found ?? NotificationEvent::fromRow(['id' => $id] + $data);
    }
}
