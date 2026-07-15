<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class ProductListFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly ?string $trackingMode,
        public readonly ?int $categoryId,
        public readonly ?int $brandId,
        public readonly ?int $warehouseId,
        public readonly bool $lowStock,
        public readonly string $sort,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $filters = is_array($query['filter'] ?? null) ? $query['filter'] : $query;
        $page = is_array($query['page'] ?? null) ? $query['page'] : [];

        return new self(
            max(1, (int) ($page['number'] ?? $query['page'] ?? 1)),
            min(100, max(1, (int) ($page['size'] ?? $query['per_page'] ?? 20))),
            self::text($filters['search'] ?? null),
            self::text($filters['status'] ?? null),
            self::text($filters['tracking_mode'] ?? null),
            isset($filters['category_id']) && $filters['category_id'] !== '' ? (int) $filters['category_id'] : null,
            isset($filters['brand_id']) && $filters['brand_id'] !== '' ? (int) $filters['brand_id'] : null,
            isset($filters['warehouse_id']) && $filters['warehouse_id'] !== '' ? (int) $filters['warehouse_id'] : null,
            filter_var($filters['low_stock'] ?? false, FILTER_VALIDATE_BOOLEAN),
            (string) ($query['sort'] ?? '-created_at'),
        );
    }

    private static function text(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
