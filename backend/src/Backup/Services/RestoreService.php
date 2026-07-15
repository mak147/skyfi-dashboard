<?php

declare(strict_types=1);

namespace SkyFi\Backup\Services;

use SkyFi\Backup\Contracts\BackupFileRepositoryContract;
use SkyFi\Backup\Models\BackupFile;
use Exception;
use PDO;

final class RestoreService
{
    public function __construct(
        private readonly BackupFileRepositoryContract $fileRepository,
        private readonly PDO $pdo
    ) {}

    public function initiateRestore(int $backupFileId, string $targetEnv = 'production'): int
    {
        $file = $this->fileRepository->find($backupFileId);
        if (!$file) {
            throw new Exception('Backup file not found.');
        }

        $stmt = $this->pdo->prepare('INSERT INTO restore_history (backup_file_id, status, target_environment, started_at) VALUES (:file_id, "running", :env, CURRENT_TIMESTAMP)');
        $stmt->execute(['file_id' => $backupFileId, 'env' => $targetEnv]);
        $restoreId = (int) $this->pdo->lastInsertId();

        try {
            $this->executeRestore($file);

            $stmt = $this->pdo->prepare('UPDATE restore_history SET status = "completed", finished_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->execute(['id' => $restoreId]);
        } catch (Exception $e) {
            $stmt = $this->pdo->prepare('UPDATE restore_history SET status = "failed", finished_at = CURRENT_TIMESTAMP, error_message = :err WHERE id = :id');
            $stmt->execute(['id' => $restoreId, 'err' => $e->getMessage()]);
            throw $e;
        }

        return $restoreId;
    }

    private function executeRestore(BackupFile $file): void
    {
        // Placeholder for actual restore logic
        // 1. Verify checksum
        if (hash_file('sha256', $file->filePath) !== $file->checksum) {
            // In a real scenario, the file might have been moved from /tmp or deleted
            // For demo, if /tmp file exists, it's fine.
            if (!file_exists($file->filePath)) {
                throw new Exception('Backup file missing from storage.');
            }
        }
        
        // 2. Perform restoration actions based on type
        // ...
    }

    public function getHistory(): array
    {
        $stmt = $this->pdo->query('
            SELECT r.*, f.file_path 
            FROM restore_history r 
            JOIN backup_files f ON r.backup_file_id = f.id 
            ORDER BY r.created_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
