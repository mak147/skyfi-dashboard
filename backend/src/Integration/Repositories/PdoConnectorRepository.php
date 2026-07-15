<?php

declare(strict_types=1);

namespace SkyFi\Integration\Repositories;

use PDO;
use SkyFi\Integration\Contracts\ConnectorRepositoryContract;
use SkyFi\Integration\DomainModels\ConnectorConfiguration;

final class PdoConnectorRepository implements ConnectorRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM connector_configurations ORDER BY connector_type');
        if (!$stmt) {
            return [];
        }

        return array_map(
            static fn(array $row): ConnectorConfiguration => ConnectorConfiguration::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );
    }

    public function findByType(string $connectorType): ?ConnectorConfiguration
    {
        $stmt = $this->pdo->prepare('SELECT * FROM connector_configurations WHERE connector_type = :type LIMIT 1');
        $stmt->execute(['type' => $connectorType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ConnectorConfiguration::fromRow($row) : null;
    }

    public function create(array $data): ConnectorConfiguration
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO connector_configurations (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
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
        $fetch = $this->pdo->prepare('SELECT * FROM connector_configurations WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return ConnectorConfiguration::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function update(int $id, array $data): ?ConnectorConfiguration
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
            return $this->findByTypeForId($id);
        }
        $this->pdo->prepare('UPDATE connector_configurations SET ' . implode(', ', $sets) . ' WHERE id = :id')
            ->execute($params);

        return $this->findByTypeForId($id);
    }

    private function findByTypeForId(int $id): ?ConnectorConfiguration
    {
        $stmt = $this->pdo->prepare('SELECT * FROM connector_configurations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ConnectorConfiguration::fromRow($row) : null;
    }
}
