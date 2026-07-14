<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DTOs;

final class RouterListFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly ?string $search = null,
        public readonly ?int $routerGroupId = null,
        public readonly ?int $tagId = null,
        public readonly ?string $site = null,
        public readonly ?string $status = null,
        public readonly ?bool $isEnabled = null,
        public readonly string $sort = '-created_at',
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $filter = isset($query['filter']) && is_array($query['filter']) ? $query['filter'] : [];
        $page = isset($query['page']) && is_array($query['page']) ? $query['page'] : [];
        $perPage = max(1, min(100, (int) ($page['size'] ?? 15)));

        return new self(
            page: max(1, (int) ($page['number'] ?? 1)),
            perPage: $perPage,
            search: self::stringOrNull($filter['search'] ?? null),
            routerGroupId: self::positiveIntOrNull($filter['router_group_id'] ?? null),
            tagId: self::positiveIntOrNull($filter['tag_id'] ?? null),
            site: self::stringOrNull($filter['site'] ?? null),
            status: self::stringOrNull($filter['status'] ?? null),
            isEnabled: array_key_exists('is_enabled', $filter)
                ? filter_var($filter['is_enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null,
            sort: (string) ($query['sort'] ?? '-created_at'),
        );
    }

    private static function positiveIntOrNull(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
