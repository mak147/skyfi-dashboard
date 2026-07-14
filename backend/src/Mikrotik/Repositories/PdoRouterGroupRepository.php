<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Repositories;

use PDO;
use SkyFi\Mikrotik\Contracts\RouterGroupRepositoryContract;
use SkyFi\Mikrotik\DomainModels\RouterGroup;

final class PdoRouterGroupRepository implements RouterGroupRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo->query(
            'SELECT g.*, COUNT(r.id) AS router_count
             FROM mikrotik_router_groups g
             LEFT JOIN mikrotik_routers r ON r.router_group_id = g.id AND r.deleted_at IS NULL
             GROUP BY g.id
             ORDER BY g.name ASC',
        )->fetchAll();

        return array_map(static fn (array $row): RouterGroup => RouterGroup::fromRow($row), $rows);
    }

    public function find(int $id): ?RouterGroup
    {
        $statement = $this->pdo->prepare('SELECT * FROM mikrotik_router_groups WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : RouterGroup::fromRow($row);
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM mikrotik_router_groups WHERE name = :name';
        $params = ['name' => $name];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data): RouterGroup
    {
        $statement = $this->pdo->prepare('INSERT INTO mikrotik_router_groups (name, description) VALUES (:name, :description)');
        $statement->execute($data);

        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('Created router group was not found.');
    }

    public function update(int $id, array $data): RouterGroup
    {
        $statement = $this->pdo->prepare('UPDATE mikrotik_router_groups SET name = :name, description = :description WHERE id = :id');
        $statement->execute([...$data, 'id' => $id]);

        return $this->find($id) ?? throw new \RuntimeException('Updated router group was not found.');
    }

    public function hasActiveRouters(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM mikrotik_routers WHERE router_group_id = :id AND deleted_at IS NULL');
        $statement->execute(['id' => $id]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM mikrotik_router_groups WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
