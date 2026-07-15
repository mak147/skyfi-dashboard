<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class VendorListFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $category = null,
        public readonly ?float $minRating = null,
        public readonly ?string $sortBy = null,
        public readonly string $sortDir = 'asc',
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        return new self(
            search: isset($query['search']) && is_string($query['search']) ? trim($query['search']) : null,
            status: isset($query['status']) && is_string($query['status']) ? trim($query['status']) : null,
            category: isset($query['category']) && is_string($query['category']) ? trim($query['category']) : null,
            minRating: isset($query['min_rating']) && is_numeric($query['min_rating']) ? (float) $query['min_rating'] : null,
            sortBy: isset($query['sort']) && is_string($query['sort']) ? trim($query['sort']) : null,
            sortDir: isset($query['dir']) && in_array(strtolower((string) $query['dir']), ['asc', 'desc'], true) ? strtolower((string) $query['dir']) : 'asc',
            page: max(1, (int) ($query['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($query['per_page'] ?? 25))),
        );
    }
}
