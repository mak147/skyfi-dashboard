<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\DTOs;

final class PurchaseRequestListFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $priority = null,
        public readonly ?string $sortBy = null,
        public readonly string $sortDir = 'desc',
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        return new self(
            search: isset($query['search']) && is_string($query['search']) ? trim($query['search']) : null,
            status: isset($query['status']) && is_string($query['status']) ? $query['status'] : null,
            priority: isset($query['priority']) && is_string($query['priority']) ? $query['priority'] : null,
            sortBy: isset($query['sort']) && is_string($query['sort']) ? $query['sort'] : null,
            sortDir: isset($query['dir']) && in_array(strtolower((string) $query['dir']), ['asc', 'desc'], true) ? strtolower((string) $query['dir']) : 'desc',
            page: max(1, (int) ($query['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($query['per_page'] ?? 25))),
        );
    }
}
