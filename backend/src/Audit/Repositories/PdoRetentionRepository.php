<?php

declare(strict_types=1);

namespace SkyFi\Audit\Repositories;

use PDO;
use SkyFi\Audit\Contracts\RetentionRepositoryContract;
use SkyFi\Audit\DomainModels\RetentionPolicy;
use SkyFi\Audit\DTOs\RetentionPolicyData;

final class PdoRetentionRepository implements RetentionRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM retention_policies ORDER BY created_at DESC');
        return array_map(
            static fn(array $row): RetentionPolicy => RetentionPolicy::fromRow($row),
            $stmt->fetchAll() ?: [],
        );
    }

    public function find(int $id): ?RetentionPolicy
    {
        $stmt = $this->pdo->prepare('SELECT * FROM retention_policies WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return RetentionPolicy::fromRow($row);
    }

    public function create(RetentionPolicyData $data): RetentionPolicy
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO retention_policies (name, description, module, action_pattern, retention_days, auto_archive, archive_location, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data->name,
            $data->description,
            $data->module,
            $data->actionPattern,
            $data->retentionDays,
            $data->autoArchive,
            $data->archiveLocation,
            $data->isActive,
            $data->createdBy,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? RetentionPolicy::fromRow(['id' => $id]);
    }

    public function update(int $id, RetentionPolicyData $data): ?RetentionPolicy
    {
        $stmt = $this->pdo->prepare(
            'UPDATE retention_policies SET name = ?, description = ?, module = ?, action_pattern = ?, retention_days = ?, auto_archive = ?, archive_location = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data->name,
            $data->description,
            $data->module,
            $data->actionPattern,
            $data->retentionDays,
            $data->autoArchive,
            $data->archiveLocation,
            $data->isActive,
            $id,
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE retention_policies SET is_active = 0 WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
