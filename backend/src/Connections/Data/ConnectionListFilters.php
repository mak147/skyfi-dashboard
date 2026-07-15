<?php

declare(strict_types=1);

namespace SkyFi\Connections\Data;

use SkyFi\Shared\Http\PaginationInput;

final class ConnectionListFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
        public readonly ?int $customerId = null,
        public readonly ?int $packageId = null,
        public readonly ?string $search = null,
        public readonly string $sort = '-created_at',
    ) {
    }

    public static function fromQuery(array $query): self
    {
        return new self(
            page: PaginationInput::page($query),
            perPage: PaginationInput::perPage($query),
            status: $query['filter']['status'] ?? null,
            type: $query['filter']['type'] ?? null,
            customerId: isset($query['filter']['customer_id']) ? (int) $query['filter']['customer_id'] : null,
            packageId: isset($query['filter']['package_id']) ? (int) $query['filter']['package_id'] : null,
            search: $query['filter']['search'] ?? null,
            sort: $query['sort'] ?? '-created_at',
        );
    }
}
