<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class TowerListFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $towerType = null,
        public readonly ?int $popSiteId = null,
        public readonly ?string $city = null,
        public readonly ?string $region = null,
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
            'tower_type' => $this->towerType,
            'pop_site_id' => $this->popSiteId,
            'city' => $this->city,
            'region' => $this->region,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort' => $this->sort,
        ];
    }
}