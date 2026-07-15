<?php

declare(strict_types=1);

namespace SkyFi\Audit\DTOs;

final class ActivityFilters
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly ?string $module = null,
        public readonly ?string $action = null,
        public readonly ?string $resourceType = null,
        public readonly ?int $resourceId = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);

        $userId = isset($query['user_id']) ? (int) $query['user_id'] : null;
        $resourceId = isset($query['resource_id']) ? (int) $query['resource_id'] : null;

        return new self(
            userId: $userId > 0 ? $userId : null,
            module: self::filterVal($query, 'module'),
            action: self::filterVal($query, 'action'),
            resourceType: self::filterVal($query, 'resource_type'),
            resourceId: $resourceId > 0 ? $resourceId : null,
            dateFrom: self::filterVal($query, 'date_from'),
            dateTo: self::filterVal($query, 'date_to'),
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
