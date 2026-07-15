<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class AssetListFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly ?int $productId,
        public readonly ?int $categoryId,
        public readonly ?string $assignmentType,
        public readonly ?int $warehouseId,
        public readonly ?int $customerId,
        public readonly ?int $towerId,
        public readonly ?int $popSiteId,
        public readonly ?int $technicianId,
        public readonly ?string $warranty,
        public readonly string $sort,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $f = is_array($query['filter'] ?? null) ? $query['filter'] : $query;
        $p = is_array($query['page'] ?? null) ? $query['page'] : [];
        $id = static fn(string $key): ?int => isset($f[$key]) && $f[$key] !== '' ? (int) $f[$key] : null;
        $text = static fn(string $key): ?string => trim((string) ($f[$key] ?? '')) ?: null;
        return new self(
            max(1, (int) ($p['number'] ?? $query['page'] ?? 1)),
            min(100, max(1, (int) ($p['size'] ?? $query['per_page'] ?? 20))),
            $text('search'),
            $text('status'),
            $id('product_id'),
            $id('category_id'),
            $text('assignment_type'),
            $id('warehouse_id'),
            $id('customer_id'),
            $id('tower_id'),
            $id('pop_site_id'),
            $id('technician_id'),
            $text('warranty'),
            (string) ($query['sort'] ?? '-created_at'),
        );
    }
}
