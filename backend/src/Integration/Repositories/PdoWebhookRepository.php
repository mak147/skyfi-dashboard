<?php

declare(strict_types=1);

namespace SkyFi\Integration\Repositories;

use PDO;
use SkyFi\Integration\Contracts\WebhookRepositoryContract;
use SkyFi\Integration\DomainModels\Webhook;
use SkyFi\Integration\DTOs\WebhookListFilters;

final class PdoWebhookRepository implements WebhookRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(WebhookListFilters $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if ($filters->clientApplicationId !== null) {
            $where[] = 'client_application_id = :client_application_id';
            $params['client_application_id'] = $filters->clientApplicationId;
        }
        if ($filters->isActive !== null) {
            $where[] = 'is_active = :is_active';
            $params['is_active'] = (int) $filters->isActive;
        }
        if ($filters->isInbound !== null) {
            $where[] = 'is_inbound = :is_inbound';
            $params['is_inbound'] = (int) $filters->isInbound;
        }
        if ($filters->search !== null) {
            $where[] = '(name LIKE :search OR url LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM webhooks WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM webhooks WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(
            static fn(array $row): Webhook => Webhook::fromRow($row),
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

    public function find(int $id): ?Webhook
    {
        $stmt = $this->pdo->prepare('SELECT * FROM webhooks WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Webhook::fromRow($row) : null;
    }

    public function findActiveByEvent(string $eventKey): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM webhooks WHERE is_active = 1 AND is_inbound = 0 AND JSON_CONTAINS(events, :event)")
        ;
        $stmt->execute(['event' => '"' . $eventKey . '"']);

        return array_map(
            static fn(array $row): Webhook => Webhook::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );
    }

    public function findInboundByEventType(string $eventType): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM webhooks WHERE is_active = 1 AND is_inbound = 1 AND JSON_CONTAINS(events, :event)")
        ;
        $stmt->execute(['event' => '"' . $eventType . '"']);

        return array_map(
            static fn(array $row): Webhook => Webhook::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );
    }

    public function create(array $data): Webhook
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO webhooks (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
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
        $fetch = $this->pdo->prepare('SELECT * FROM webhooks WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return Webhook::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function update(int $id, array $data): ?Webhook
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
        $this->pdo->prepare('UPDATE webhooks SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM webhooks WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
