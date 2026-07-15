<?php

declare(strict_types=1);

namespace SkyFi\Backup\Repositories;

use PDO;
use SkyFi\Backup\Contracts\BackupFileRepositoryContract;
use SkyFi\Backup\Models\BackupFile;

final class PdoBackupFileRepository implements BackupFileRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $id): ?BackupFile
    {
        $stmt = $this->pdo->prepare('
            SELECT f.*, p.name as storage_provider_name 
            FROM backup_files f 
            JOIN backup_storage_providers p ON f.storage_provider_id = p.id 
            WHERE f.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? BackupFile::fromRow($row) : null;
    }

    public function list(array $filters): array
    {
        $page = $filters['page'] ?? 1;
        $perPage = $filters['perPage'] ?? 20;
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare('
            SELECT f.*, p.name as storage_provider_name 
            FROM backup_files f 
            JOIN backup_storage_providers p ON f.storage_provider_id = p.id 
            ORDER BY f.created_at DESC 
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = BackupFile::fromRow($row);
        }

        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM backup_files')->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => (int) ceil($total / $perPage),
        ];
    }

    public function create(array $data): BackupFile
    {
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }

        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ":$c", $cols);
        $sql = sprintf('INSERT INTO backup_files (%s) VALUES (%s)', implode(',', $cols), implode(',', $placeholders));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->find((int)$this->pdo->lastInsertId());
    }

    public function update(int $id, array $data): BackupFile
    {
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }

        $sets = array_map(fn($c) => "$c = :$c", array_keys($data));
        $sql = sprintf('UPDATE backup_files SET %s WHERE id = :id', implode(',', $sets));
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, ['id' => $id]));
        
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM backup_files WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function deleteExpired(): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM backup_files WHERE expires_at < CURRENT_TIMESTAMP');
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function addVerification(int $fileId, string $status, ?string $details): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO verification_history (backup_file_id, status, details) VALUES (:file_id, :status, :details)');
        $stmt->execute(['file_id' => $fileId, 'status' => $status, 'details' => $details]);
        
        $this->pdo->prepare('UPDATE backup_files SET verified_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['id' => $fileId]);
    }

    public function getVerificationHistory(int $fileId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM verification_history WHERE backup_file_id = :file_id ORDER BY created_at DESC');
        $stmt->execute(['file_id' => $fileId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
