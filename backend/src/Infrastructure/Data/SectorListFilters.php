<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class SectorListFilters
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?int $towerId = null,
        public readonly ?int $deviceId = null,
        public readonly ?int $frequencyMhz = null,
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
            'tower_id' => $this->towerId,
            'device_id' => $this->deviceId,
            'frequency_mhz' => $this->frequencyMhz,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort' => $this->sort,
        ];
    }
}