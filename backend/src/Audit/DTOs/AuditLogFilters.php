<?php

declare(strict_types=1);

namespace SkyFi\Audit\DTOs;

final class AuditLogFilters
{
    public function __construct(
        public readonly ?string $module = null,
        public readonly ?string $action = null,
        public readonly ?string $entityType = null,
        public readonly ?int $entityId = null,
        public readonly ?int $userId = null,
        public readonly ?string $severity = null,
        public readonly ?string $correlationId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);

        $entityId = isset($query['entity_id']) ? (int) $query['entity_id'] : null;
        $userId = isset($query['user_id']) ? (int) $query['user_id'] : null;

        return new self(
            module: self::filterVal($query, 'module'),
            action: self::filterVal($query, 'action'),
            entityType: self::filterVal($query, 'entity_type'),
            entityId: $entityId > 0 ? $entityId : null,
            userId: $userId > 0 ? $userId : null,
            severity: self::filterVal($query, 'severity'),
            correlationId: self::filterVal($query, 'correlation_id'),
            dateFrom: self::filterVal($query, 'date_from'),
            dateTo: self::filterVal($query, 'date_to'),
            search: self::filterVal($query, 'search'),
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );
    }

    /** @param array<string, mixed> $query */
    private static function filterVal(array $query, string $key): ?string
    {
        $val = $query[$key] ?? $query['filter'][$key] ?? null;
        if ($val === null || $val === '') {
            return null;
        }
        return (string) $val;
    }
}
