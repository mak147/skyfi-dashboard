<?php

declare(strict_types=1);

namespace SkyFi\Customers\Data;

final class CustomerListFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $status,
        public readonly ?string $city,
        public readonly ?string $area,
        public readonly ?string $search,
        public readonly string $sort,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = isset($query['page']['number']) && is_numeric($query['page']['number']) ? (int) $query['page']['number'] : 1;
        $perPage = isset($query['page']['size']) && is_numeric($query['page']['size']) ? (int) $query['page']['size'] : 15;

        if ($page < 1) {
            $page = 1;
        }
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $filter = $query['filter'] ?? [];
        $status = isset($filter['status']) && is_string($filter['status']) && $filter['status'] !== '' ? $filter['status'] : null;
        $city = isset($filter['city']) && is_string($filter['city']) && $filter['city'] !== '' ? $filter['city'] : null;
        $area = isset($filter['area']) && is_string($filter['area']) && $filter['area'] !== '' ? $filter['area'] : null;
        $search = isset($filter['search']) && is_string($filter['search']) && $filter['search'] !== '' ? $filter['search'] : null;

        $sort = isset($query['sort']) && is_string($query['sort']) && $query['sort'] !== '' ? $query['sort'] : '-created_at';

        return new self(
            page: $page,
            perPage: $perPage,
            status: $status,
            city: $city,
            area: $area,
            search: $search,
            sort: $sort,
        );
    }
}
