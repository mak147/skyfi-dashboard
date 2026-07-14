<?php

declare(strict_types=1);

namespace SkyFi\Connections\Data;

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
            page: isset($query['page']['number']) ? (int) $query['page']['number'] : 1,
            perPage: isset($query['page']['size']) ? (int) $query['page']['size'] : 15,
            status: $query['filter']['status'] ?? null,
            type: $query['filter']['type'] ?? null,
            customerId: isset($query['filter']['customer_id']) ? (int) $query['filter']['customer_id'] : null,
            packageId: isset($query['filter']['package_id']) ? (int) $query['filter']['package_id'] : null,
            search: $query['filter']['search'] ?? null,
            sort: $query['sort'] ?? '-created_at',
        );
    }
}
