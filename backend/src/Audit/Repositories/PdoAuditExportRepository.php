<?php

declare(strict_types=1);

namespace SkyFi\Audit\Repositories;

use PDO;
use SkyFi\Audit\Contracts\AuditExportRepositoryContract;
use SkyFi\Audit\DomainModels\AuditExport;

final class PdoAuditExportRepository implements AuditExportRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM audit_exports WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
        $stmt->execute([$userId]);
        return array_map(
            static fn(array $row): AuditExport => AuditExport::fromRow($row),
            $stmt->fetchAll() ?: [],
        );
    }

    public function find(int $id): ?AuditExport
    {
        $stmt = $this->pdo->prepare('SELECT * FROM audit_exports WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return AuditExport::fromRow($row);
    }

    public function create(int $userId, string $format, array $filters): AuditExport
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_exports (user_id, format, filters, status, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $userId,
            $format,
            json_encode($filters, JSON_THROW_ON_ERROR),
            'pending',
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? AuditExport::fromRow(['id' => $id]);
    }

    public function updateStatus(int $id, string $status, ?string $filePath = null, ?string $errorMessage = null, int $rowCount = 0): ?AuditExport
    {
        $completedAt = ($status === 'completed' || $status === 'failed') ? date('Y-m-d H:i:s') : null;

        $stmt = $this->pdo->prepare(
            'UPDATE audit_exports SET status = ?, file_path = ?, error_message = ?, row_count = ?, completed_at = ? WHERE id = ?'
        );
        $stmt->execute([$status, $filePath, $errorMessage, $rowCount, $completedAt, $id]);

        return $this->find($id);
    }
}
