<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class HotspotUserListFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly ?string $search = null,
        public readonly ?int $customerId = null,
        public readonly ?int $routerId = null,
        public readonly ?int $profileId = null,
        public readonly ?int $packageId = null,
        public readonly ?string $status = null,
        public readonly ?string $syncStatus = null,
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
            customerId: self::positiveIntOrNull($filter['customer_id'] ?? null),
            routerId: self::positiveIntOrNull($filter['router_id'] ?? null),
            profileId: self::positiveIntOrNull($filter['profile_id'] ?? null),
            packageId: self::positiveIntOrNull($filter['package_id'] ?? null),
            status: self::stringOrNull($filter['status'] ?? null),
            syncStatus: self::stringOrNull($filter['sync_status'] ?? null),
            sort: (string) ($query['sort'] ?? '-created_at'),
        );
    }

    private static function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $integer = (int) $value;
        return $integer > 0 ? $integer : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $trimmed = trim((string) $value);
        return $trimmed !== '' ? $trimmed : null;
    }
}
