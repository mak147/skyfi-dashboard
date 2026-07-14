<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class PopSiteListFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $city = null,
        public readonly ?string $region = null,
        public readonly ?string $powerStatus = null,
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sort = '-created_at',
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'city' => $this->city,
            'region' => $this->region,
            'power_status' => $this->powerStatus,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort' => $this->sort,
        ];
    }
}