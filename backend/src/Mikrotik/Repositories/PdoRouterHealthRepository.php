<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Repositories;

use PDO;
use SkyFi\Mikrotik\Contracts\RouterHealthRepositoryContract;
use SkyFi\Mikrotik\DomainModels\RouterHealthSnapshot;

final class PdoRouterHealthRepository implements RouterHealthRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(RouterHealthSnapshot $snapshot): RouterHealthSnapshot
    {
        $attributes = $snapshot->toArray();
        unset($attributes['id']);
        $columns = array_keys($attributes);
        $statement = $this->pdo->prepare(sprintf(
            'INSERT INTO mikrotik_router_health_snapshots (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns)),
        ));
        $statement->execute($attributes);

        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('Created health snapshot was not found.');
    }

    public function latestForRouter(int $routerId): ?RouterHealthSnapshot
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM mikrotik_router_health_snapshots WHERE router_id = :router_id ORDER BY checked_at DESC, id DESC LIMIT 1',
        );
        $statement->execute(['router_id' => $routerId]);
        $row = $statement->fetch();

        return $row === false ? null : RouterHealthSnapshot::fromRow($row);
    }

    private function find(int $id): ?RouterHealthSnapshot
    {
        $statement = $this->pdo->prepare('SELECT * FROM mikrotik_router_health_snapshots WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : RouterHealthSnapshot::fromRow($row);
    }
}
