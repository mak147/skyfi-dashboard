<?php

declare(strict_types=1);

namespace SkyFi\Integration\Repositories;

use PDO;
use SkyFi\Integration\Contracts\EventRegistryRepositoryContract;
use SkyFi\Integration\DomainModels\EventRegistryEntry;

final class PdoEventRegistryRepository implements EventRegistryRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(int $page = 1, int $perPage = 25, ?string $sourceModule = null): array
    {
        $where = '1=1';
        $params = [];
        if ($sourceModule !== null) {
            $where = 'source_module = :source_module';
            $params['source_module'] = $sourceModule;
        }

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM event_registry WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM event_registry WHERE {$where} ORDER BY source_module, event_key LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(
            static fn(array $row): EventRegistryEntry => EventRegistryEntry::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );

        return [
            'items' => $items,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function find(int $id): ?EventRegistryEntry
    {
        $stmt = $this->pdo->prepare('SELECT * FROM event_registry WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? EventRegistryEntry::fromRow($row) : null;
    }

    public function findByKey(string $eventKey): ?EventRegistryEntry
    {
        $stmt = $this->pdo->prepare('SELECT * FROM event_registry WHERE event_key = :event_key LIMIT 1');
        $stmt->execute(['event_key' => $eventKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? EventRegistryEntry::fromRow($row) : null;
    }

    public function create(array $data): EventRegistryEntry
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO event_registry (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
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
        $fetch = $this->pdo->prepare('SELECT * FROM event_registry WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return EventRegistryEntry::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function update(int $id, array $data): ?EventRegistryEntry
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
        $this->pdo->prepare('UPDATE event_registry SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);

        return $this->find($id);
    }

    public function allActiveKeys(): array
    {
        $stmt = $this->pdo->query("SELECT event_key FROM event_registry WHERE is_active = 1 ORDER BY event_key");

        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    public function sourceModules(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT source_module FROM event_registry WHERE is_active = 1 ORDER BY source_module");

        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }
}
