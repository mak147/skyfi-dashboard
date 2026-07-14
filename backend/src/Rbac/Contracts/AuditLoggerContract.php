<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Contracts;

interface AuditLoggerContract
{
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void;
}
