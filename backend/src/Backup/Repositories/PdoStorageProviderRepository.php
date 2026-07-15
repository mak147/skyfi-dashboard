<?php

declare(strict_types=1);

namespace SkyFi\Backup\Repositories;

use PDO;
use SkyFi\Backup\Models\StorageProvider;

final class PdoStorageProviderRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?StorageProvider
    {
        $stmt = $this->pdo->prepare('SELECT * FROM backup_storage_providers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? StorageProvider::fromRow($row) : null;
    }

    public function findDefault(): ?StorageProvider
    {
        $stmt = $this->pdo->query('SELECT * FROM backup_storage_providers WHERE is_default = 1 LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? StorageProvider::fromRow($row) : null;
    }

    public function list(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM backup_storage_providers ORDER BY name ASC');
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = StorageProvider::fromRow($row);
        }
        return $items;
    }

    public function create(array $data): StorageProvider
    {
        if (isset($data['config'])) {
            $data['config'] = json_encode($data['config']);
        }
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = sprintf('INSERT INTO backup_storage_providers (%s) VALUES (%s)', implode(',', $cols), implode(',', $placeholders));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->find((int)$this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): StorageProvider
    {
        if (isset($data['config'])) {
            $data['config'] = json_encode($data['config']);
        }
        $sets = array_map(fn($c) => "$c = :$c", array_keys($data));
        $sql = sprintf('UPDATE backup_storage_providers SET %s WHERE id = :id', implode(',', $sets));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, ['id' => $id]));
        
        return $this->find($id);
    }
}
