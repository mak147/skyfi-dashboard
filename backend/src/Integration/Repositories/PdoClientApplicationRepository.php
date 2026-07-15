<?php

declare(strict_types=1);

namespace SkyFi\Integration\Repositories;

use PDO;
use SkyFi\Integration\Contracts\ClientApplicationRepositoryContract;
use SkyFi\Integration\DomainModels\ClientApplication;

final class PdoClientApplicationRepository implements ClientApplicationRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(int $page = 1, int $perPage = 25): array
    {
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM client_applications');
        $count->execute();
        $total = (int) $count->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT * FROM client_applications ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(
            static fn(array $row): ClientApplication => ClientApplication::fromRow($row),
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

    public function find(int $id): ?ClientApplication
    {
        $stmt = $this->pdo->prepare('SELECT * FROM client_applications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ClientApplication::fromRow($row) : null;
    }

    public function create(array $data): ClientApplication
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO client_applications (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
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
        $fetch = $this->pdo->prepare('SELECT * FROM client_applications WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return ClientApplication::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function update(int $id, array $data): ?ClientApplication
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
        $this->pdo->prepare('UPDATE client_applications SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM client_applications WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
