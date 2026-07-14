<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Repositories;

use PDO;
use SkyFi\Mikrotik\Contracts\RouterRepositoryContract;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\DomainModels\Router;

final class PdoRouterRepository implements RouterRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(RouterListFilters $filters): array
    {
        [$where, $params] = $this->whereForFilters($filters);
        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM mikrotik_routers r WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $allowedSorts = ['name', 'host', 'site', 'last_connection_status', 'created_at', 'updated_at'];
        $sortField = ltrim($filters->sort, '-');
        $sortField = in_array($sortField, $allowedSorts, true) ? $sortField : 'created_at';
        $sortOrder = str_starts_with($filters->sort, '-') ? 'DESC' : 'ASC';
        $offset = ($filters->page - 1) * $filters->perPage;

        $statement = $this->pdo->prepare(
            "SELECT r.*, g.name AS router_group_name
             FROM mikrotik_routers r
             LEFT JOIN mikrotik_router_groups g ON g.id = r.router_group_id
             WHERE {$whereSql}
             ORDER BY r.{$sortField} {$sortOrder}, r.id DESC
             LIMIT {$filters->perPage} OFFSET {$offset}",
        );
        $statement->execute($params);

        return [
            'items' => $this->hydrateRouters($statement->fetchAll()),
            'total' => $total,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'lastPage' => max(1, (int) ceil($total / $filters->perPage)),
        ];
    }

    public function find(int $id): ?Router
    {
        $statement = $this->pdo->prepare(
            'SELECT r.*, g.name AS router_group_name
             FROM mikrotik_routers r
             LEFT JOIN mikrotik_router_groups g ON g.id = r.router_group_id
             WHERE r.id = :id AND r.deleted_at IS NULL',
        );
        $statement->execute(['id' => $id]);
        $routers = $this->hydrateRouters($statement->fetchAll());

        return $routers[0] ?? null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM mikrotik_routers WHERE name = :name AND deleted_at IS NULL';
        $params = ['name' => $name];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data): Router
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO mikrotik_routers (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns)),
        );
        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return $this->find((int) $this->pdo->lastInsertId()) ?? throw new \RuntimeException('Created router was not found.');
    }

    public function update(int $id, array $data): Router
    {
        $sets = implode(', ', array_map(static fn (string $column): string => "{$column} = :{$column}", array_keys($data)));
        $statement = $this->pdo->prepare("UPDATE mikrotik_routers SET {$sets} WHERE id = :id AND deleted_at IS NULL");
        $statement->execute([...$data, 'id' => $id]);

        return $this->find($id) ?? throw new \RuntimeException('Updated router was not found.');
    }

    public function softDelete(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE mikrotik_routers SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
        $statement->execute(['id' => $id]);
    }

    public function syncTags(int $routerId, array $tagIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM mikrotik_router_tag_assignments WHERE router_id = :router_id');
            $delete->execute(['router_id' => $routerId]);
            if ($tagIds !== []) {
                $insert = $this->pdo->prepare('INSERT INTO mikrotik_router_tag_assignments (router_id, tag_id) VALUES (:router_id, :tag_id)');
                foreach ($tagIds as $tagId) {
                    $insert->execute(['router_id' => $routerId, 'tag_id' => $tagId]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function updateConnectionStatus(int $routerId, array $status): void
    {
        $this->update($routerId, $status);
    }

    public function updateDiscoveryMetadata(int $routerId, array $metadata): void
    {
        $this->update($routerId, $metadata);
    }

    /** @return array{0: array<int, string>, 1: array<string, mixed>} */
    private function whereForFilters(RouterListFilters $filters): array
    {
        $where = ['r.deleted_at IS NULL'];
        $params = [];
        if ($filters->search !== null) {
            $where[] = '(r.name LIKE :search OR r.host LIKE :search OR r.site LIKE :search OR r.location LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }
        if ($filters->routerGroupId !== null) {
            $where[] = 'r.router_group_id = :router_group_id';
            $params['router_group_id'] = $filters->routerGroupId;
        }
        if ($filters->tagId !== null) {
            $where[] = 'EXISTS (SELECT 1 FROM mikrotik_router_tag_assignments a WHERE a.router_id = r.id AND a.tag_id = :tag_id)';
            $params['tag_id'] = $filters->tagId;
        }
        if ($filters->site !== null) {
            $where[] = 'r.site = :site';
            $params['site'] = $filters->site;
        }
        if ($filters->status !== null && in_array($filters->status, ['online', 'offline', 'unknown', 'disabled'], true)) {
            $where[] = 'r.last_connection_status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->isEnabled !== null) {
            $where[] = 'r.is_enabled = :is_enabled';
            $params['is_enabled'] = $filters->isEnabled ? 1 : 0;
        }

        return [$where, $params];
    }

    /** @param array<int, array<string, mixed>> $rows @return array<int, Router> */
    private function hydrateRouters(array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $tagStatement = $this->pdo->prepare(
            "SELECT a.router_id, t.id, t.name, t.color
             FROM mikrotik_router_tag_assignments a
             INNER JOIN mikrotik_router_tags t ON t.id = a.tag_id
             WHERE a.router_id IN ({$placeholders})
             ORDER BY t.name ASC",
        );
        $tagStatement->execute($ids);
        $tagsByRouter = [];
        foreach ($tagStatement->fetchAll() as $tag) {
            $tagsByRouter[(int) $tag['router_id']][] = [
                'id' => (int) $tag['id'],
                'name' => (string) $tag['name'],
                'color' => $tag['color'],
            ];
        }

        return array_map(static function (array $row) use ($tagsByRouter): Router {
            $row['tags'] = $tagsByRouter[(int) $row['id']] ?? [];

            return Router::fromRow($row);
        }, $rows);
    }
}
