<?php

declare(strict_types=1);

namespace SkyFi\Integration\Repositories;

use PDO;
use SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract;
use SkyFi\Integration\DomainModels\WebhookDelivery;
use SkyFi\Integration\DTOs\DeliveryListFilters;

final class PdoWebhookDeliveryRepository implements WebhookDeliveryRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(DeliveryListFilters $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if ($filters->webhookId !== null) {
            $where[] = 'webhook_id = :webhook_id';
            $params['webhook_id'] = $filters->webhookId;
        }
        if ($filters->eventKey !== null) {
            $where[] = 'event_key = :event_key';
            $params['event_key'] = $filters->eventKey;
        }
        if ($filters->status !== null) {
            $where[] = 'status = :status';
            $params['status'] = $filters->status;
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM webhook_deliveries WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM webhook_deliveries WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(
            static fn(array $row): WebhookDelivery => WebhookDelivery::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );

        return [
            'items' => $items,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / $filters->perPage)),
        ];
    }

    public function find(int $id): ?WebhookDelivery
    {
        $stmt = $this->pdo->prepare('SELECT * FROM webhook_deliveries WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? WebhookDelivery::fromRow($row) : null;
    }

    public function create(array $data): WebhookDelivery
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO webhook_deliveries (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $stmt->bindValue($key, json_encode($value, JSON_THROW_ON_ERROR));
            } elseif (is_bool($value)) {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
            } elseif ($value === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $id = (int) $this->pdo->lastInsertId();
        $fetch = $this->pdo->prepare('SELECT * FROM webhook_deliveries WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return WebhookDelivery::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function update(int $id, array $data): ?WebhookDelivery
    {
        $sets = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if ($key === 'id') {
                continue;
            }
            $sets[] = "{$key} = :set_{$key}";
            if (is_array($value)) {
                $params["set_{$key}"] = json_encode($value, JSON_THROW_ON_ERROR);
            } elseif (is_bool($value)) {
                $params["set_{$key}"] = (int) $value;
            } else {
                $params["set_{$key}"] = $value;
            }
        }
        if ($sets === []) {
            return $this->find($id);
        }
        $this->pdo->prepare('UPDATE webhook_deliveries SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);

        return $this->find($id);
    }

    public function findPendingRetries(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM webhook_deliveries WHERE status = 'retrying' AND next_retry_at <= NOW() ORDER BY next_retry_at ASC LIMIT 100"
        );
        $stmt->execute();

        return array_map(
            static fn(array $row): WebhookDelivery => WebhookDelivery::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );
    }
}
