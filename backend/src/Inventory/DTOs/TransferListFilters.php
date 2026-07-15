<?php

declare(strict_types=1);

namespace SkyFi\Inventory\DTOs;

final class TransferListFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly ?int $sourceWarehouseId,
        public readonly ?int $destinationWarehouseId,
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
            $text('status'),
            isset($f['source_warehouse_id']) && $f['source_warehouse_id'] !== '' ? (int) $f['source_warehouse_id'] : null,
            isset($f['destination_warehouse_id']) && $f['destination_warehouse_id'] !== '' ? (int) $f['destination_warehouse_id'] : null,
            (string) ($query['sort'] ?? '-requested_at'),
        );
    }
}
