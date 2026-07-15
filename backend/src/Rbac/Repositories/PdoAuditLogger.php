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
                user_id, action, entity_type, entity_id, module, resource, severity,
                correlation_id, old_values, new_values, ip_address, user_agent, url,
                compliance_tags, is_immutable, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            $this->inferModule($action, $entityType),
            $entityType,
            $this->inferSeverity($action),
            null,
            $oldValues !== null ? json_encode($oldValues, JSON_THROW_ON_ERROR) : null,
            $newValues !== null ? json_encode($newValues, JSON_THROW_ON_ERROR) : null,
            $ipAddress,
            $userAgent,
            null,
            null,
            1,
        ]);
    }

    private function inferModule(string $action, string $entityType): string
    {
        $prefix = explode('.', $action)[0];
        if ($prefix !== $action) {
            return $prefix;
        }
        return strtolower($entityType);
    }

    private function inferSeverity(string $action): string
    {
        if (str_contains($action, 'delete') || str_contains($action, 'failed')) {
            return 'critical';
        }
        if (str_contains($action, 'changed') || str_contains($action, 'update') || str_contains($action, 'alert')) {
            return 'warning';
        }
        return 'info';
    }
}
