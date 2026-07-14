<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Repositories;

use PDO;
use SkyFi\Mikrotik\Contracts\RouterTagRepositoryContract;
use SkyFi\Mikrotik\DomainModels\RouterTag;

final class PdoRouterTagRepository implements RouterTagRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo->query('SELECT * FROM mikrotik_router_tags ORDER BY name ASC')->fetchAll();

        return array_map(static fn (array $row): RouterTag => RouterTag::fromRow($row), $rows);
    }

    public function find(int $id): ?RouterTag
    {
        $statement = $this->pdo->prepare('SELECT * FROM mikrotik_router_tags WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : RouterTag::fromRow($row);
    }

    public function existingIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $statement = $this->pdo->prepare('SELECT id FROM mikrotik_router_tags WHERE id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')');
        $statement->execute($ids);

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM mikrotik_router_tags WHERE name = :name';
        $params = ['name' => $name];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data): RouterTag
    {
        $statement = $this->pdo->prepare('INSERT INTO mikrotik_router_tags (name, color) VALUES (:name, :color)');
        $statement->execute($data);

        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('Created router tag was not found.');
    }

    public function update(int $id, array $data): RouterTag
    {
        $statement = $this->pdo->prepare('UPDATE mikrotik_router_tags SET name = :name, color = :color WHERE id = :id');
        $statement->execute([...$data, 'id' => $id]);

        return $this->find($id) ?? throw new \RuntimeException('Updated router tag was not found.');
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM mikrotik_router_tags WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
