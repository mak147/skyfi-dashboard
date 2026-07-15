<?php

declare(strict_types=1);

namespace SkyFi\Database;

use PDO;

/**
 * Lightweight SQL migration runner.
 *
 * Reads `.sql` files from the migrations directory, tracks which files
 * have already been applied in the `migrations` meta-table, and applies
 * any pending migrations in filename order.
 *
 * Usage:
 *   php database/migrate.php
 */
final class Migrator
{
    private const META_TABLE = '_migrations';

    public function __construct(
        private readonly PDO $pdo,
        private readonly string $migrationsDir,
    ) {
    }

    /**
     * Ensures the meta-table exists and returns the list of already-applied
     * migration filenames.
     *
     * @return array<string> Applied migration filenames.
     */
    public function getApplied(): array
    {
        $this->ensureMetaTable();

        $statement = $this->pdo->query('SELECT filename FROM ' . self::META_TABLE . ' ORDER BY applied_at ASC');
        /** @var array<string> */
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Applies all pending migrations and returns a summary.
     *
     * @return array{applied: array<string>, skipped: array<string>, errors: array<string, string>}
     */
    public function migrate(): array
    {
        $this->ensureMetaTable();

        $applied = $this->getApplied();
        $files = glob($this->migrationsDir . '/*.sql');

        if ($files === false) {
            throw new \RuntimeException('Failed to read migrations directory: ' . $this->migrationsDir);
        }

        sort($files);

        $result = [
            'applied' => [],
            'skipped' => [],
            'errors' => [],
        ];

        foreach ($files as $filePath) {
            $filename = basename($filePath);

            if (in_array($filename, $applied, true)) {
                $result['skipped'][] = $filename;
                continue;
            }

            $sql = file_get_contents($filePath);
            if ($sql === false || trim($sql) === '') {
                $result['errors'][$filename] = 'Empty or unreadable file.';
                continue;
            }

            try {
                $this->pdo->beginTransaction();
                $this->pdo->exec($sql);
                $this->record($filename);
                $this->pdo->commit();
                $result['applied'][] = $filename;
            } catch (\PDOException $e) {
                $this->pdo->rollBack();
                $result['errors'][$filename] = $e->getMessage();
            }
        }

        return $result;
    }

    private function ensureMetaTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::META_TABLE . ' (
                filename VARCHAR(255) NOT NULL PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                checksum VARCHAR(64) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function record(string $filename): void
    {
        $checksum = hash_file('sha256', $this->migrationsDir . '/' . $filename);
        $statement = $this->pdo->prepare(
            'INSERT INTO ' . self::META_TABLE . ' (filename, checksum) VALUES (:filename, :checksum)'
        );
        $statement->execute([
            'filename' => $filename,
            'checksum' => $checksum !== false ? $checksum : 'unknown',
        ]);
    }
}
