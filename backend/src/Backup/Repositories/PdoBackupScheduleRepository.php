<?php

declare(strict_types=1);

namespace SkyFi\Backup\Repositories;

use PDO;
use SkyFi\Backup\Contracts\BackupScheduleRepositoryContract;
use SkyFi\Backup\Models\BackupSchedule;

final class PdoBackupScheduleRepository implements BackupScheduleRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?BackupSchedule
    {
        $stmt = $this->pdo->prepare('
            SELECT s.*, p.name as storage_provider_name 
            FROM backup_schedules s 
            JOIN backup_storage_providers p ON s.storage_provider_id = p.id 
            WHERE s.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? BackupSchedule::fromRow($row) : null;
    }

    public function list(): array
    {
        $stmt = $this->pdo->query('
            SELECT s.*, p.name as storage_provider_name 
            FROM backup_schedules s 
            JOIN backup_storage_providers p ON s.storage_provider_id = p.id 
            ORDER BY s.name ASC
        ');
        
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = BackupSchedule::fromRow($row);
        }
        return $items;
    }

    public function create(array $data): BackupSchedule
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = sprintf('INSERT INTO backup_schedules (%s) VALUES (%s)', implode(',', $cols), implode(',', $placeholders));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->find((int)$this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): BackupSchedule
    {
        $sets = array_map(fn($c) => "$c = :$c", array_keys($data));
        $sql = sprintf('UPDATE backup_schedules SET %s WHERE id = :id', implode(',', $sets));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, ['id' => $id]));
        
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM backup_schedules WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findDue(): array
    {
        // Simple mock for due schedules based on next_run_at <= NOW
        $stmt = $this->pdo->query('SELECT * FROM backup_schedules WHERE is_active = 1 AND (next_run_at IS NULL OR next_run_at <= CURRENT_TIMESTAMP)');
        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = BackupSchedule::fromRow($row);
        }
        return $items;
    }
}
