<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class NetworkDeviceListFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?string $deviceType = null,
        public readonly ?int $popSiteId = null,
        public readonly ?int $towerId = null,
        public readonly ?int $mikrotikRouterId = null,
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
            'device_type' => $this->deviceType,
            'pop_site_id' => $this->popSiteId,
            'tower_id' => $this->towerId,
            'mikrotik_router_id' => $this->mikrotikRouterId,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort' => $this->sort,
        ];
    }
}