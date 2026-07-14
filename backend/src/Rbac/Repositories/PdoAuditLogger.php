<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Repositories;

use PDO;
use SkyFi\Rbac\Contracts\AuditLoggerContract;

final class PdoAuditLogger implements AuditLoggerContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO audit_logs (
                user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues !== null ? json_encode($oldValues, JSON_THROW_ON_ERROR) : null,
            $newValues !== null ? json_encode($newValues, JSON_THROW_ON_ERROR) : null,
            $ipAddress,
            $userAgent
        ]);
    }
}
