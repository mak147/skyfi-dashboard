<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class StockMovementListFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?string $type,
        public readonly ?int $productId,
        public readonly ?int $warehouseId,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
        public readonly string $sort,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $f = is_array($query['filter'] ?? null) ? $query['filter'] : $query;
        $p = is_array($query['page'] ?? null) ? $query['page'] : [];
        $text = static fn(string $key): ?string => trim((string) ($f[$key] ?? '')) ?: null;
        return new self(
            max(1, (int) ($p['number'] ?? $query['page'] ?? 1)),
            min(100, max(1, (int) ($p['size'] ?? $query['per_page'] ?? 20))),
            $text('search'),
            $text('type'),
            isset($f['product_id']) && $f['product_id'] !== '' ? (int) $f['product_id'] : null,
            isset($f['warehouse_id']) && $f['warehouse_id'] !== '' ? (int) $f['warehouse_id'] : null,
            $text('date_from'),
            $text('date_to'),
            (string) ($query['sort'] ?? '-occurred_at'),
        );
    }
}
