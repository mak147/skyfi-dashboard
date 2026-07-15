<?php

declare(strict_types=1);

namespace SkyFi\Integration\Repositories;

use PDO;
use SkyFi\Integration\Contracts\ApiKeyRepositoryContract;
use SkyFi\Integration\DomainModels\ApiKey;
use SkyFi\Integration\DTOs\ApiKeyListFilters;

final class PdoApiKeyRepository implements ApiKeyRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(ApiKeyListFilters $filters): array
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
        if ($filters->search !== null) {
            $where[] = '(name LIKE :search OR key_prefix LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM api_keys WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(
            static fn(array $row): ApiKey => ApiKey::fromRow($row),
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

    public function find(int $id): ?ApiKey
    {
        $stmt = $this->pdo->prepare('SELECT * FROM api_keys WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ApiKey::fromRow($row) : null;
    }

    public function findByHash(string $keyHash): ?ApiKey
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM api_keys WHERE key_hash = :key_hash AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1"
        );
        $stmt->execute(['key_hash' => $keyHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ApiKey::fromRow($row) : null;
    }

    public function findByPrefix(string $prefix): ?ApiKey
    {
        $stmt = $this->pdo->prepare('SELECT * FROM api_keys WHERE key_prefix = :prefix LIMIT 1');
        $stmt->execute(['prefix' => $prefix]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ApiKey::fromRow($row) : null;
    }

    public function create(array $data): ApiKey
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO api_keys (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
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
        $fetch = $this->pdo->prepare('SELECT * FROM api_keys WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return ApiKey::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function update(int $id, array $data): ?ApiKey
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
        $sql = 'UPDATE api_keys SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $this->pdo->prepare($sql)->execute($params);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM api_keys WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function updateLastUsed(int $id): void
    {
        $this->pdo->prepare('UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
