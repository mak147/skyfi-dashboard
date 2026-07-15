<?php

declare(strict_types=1);

namespace SkyFi\Backup\Repositories;

use PDO;
use SkyFi\Backup\Contracts\BackupJobRepositoryContract;
use SkyFi\Backup\Models\BackupJob;

final class PdoBackupJobRepository implements BackupJobRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?BackupJob
    {
        $stmt = $this->pdo->prepare('
            SELECT j.*, s.name as schedule_name 
            FROM backup_jobs j 
            LEFT JOIN backup_schedules s ON j.schedule_id = s.id 
            WHERE j.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? BackupJob::fromRow($row) : null;
    }

    public function list(array $filters): array
    {
        $page = $filters['page'] ?? 1;
        $perPage = $filters['perPage'] ?? 20;
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare('
            SELECT j.*, s.name as schedule_name 
            FROM backup_jobs j 
            LEFT JOIN backup_schedules s ON j.schedule_id = s.id 
            ORDER BY j.created_at DESC 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = BackupJob::fromRow($row);
        }

        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM backup_jobs')->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => (int) ceil($total / $perPage),
        ];
    }

    public function create(array $data): BackupJob
    {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = sprintf('INSERT INTO backup_jobs (%s) VALUES (%s)', implode(',', $cols), implode(',', $placeholders));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->find((int)$this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): BackupJob
    {
        $sets = array_map(fn($c) => "$c = :$c", array_keys($data));
        $sql = sprintf('UPDATE backup_jobs SET %s WHERE id = :id', implode(',', $sets));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, ['id' => $id]));
        
        return $this->find($id);
    }

    public function statistics(): array
    {
        $stats = $this->pdo->query("
            SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_jobs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_jobs,
                (SELECT COUNT(*) FROM backup_files) as total_files,
                (SELECT SUM(file_size) FROM backup_files) as total_size
            FROM backup_jobs
        ")->fetch(PDO::FETCH_ASSOC);

        return [
            'total_jobs' => (int)$stats['total_jobs'],
            'successful_jobs' => (int)$stats['successful_jobs'],
            'failed_jobs' => (int)$stats['failed_jobs'],
            'total_files' => (int)$stats['total_files'],
            'total_size' => (int)$stats['total_size'],
        ];
    }
}
