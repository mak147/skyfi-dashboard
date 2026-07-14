<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\HotspotProfile;
use SkyFi\Hotspot\DTOs\HotspotProfileListFilters;

interface HotspotProfileRepositoryContract
{
    /** @return array{items: array<int, HotspotProfile>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(HotspotProfileListFilters $filters): array;

    public function find(int $id): ?HotspotProfile;

    public function findByRouterAndName(int $routerId, string $routerProfileName): ?HotspotProfile;

    /** @return array<int, HotspotProfile> */
    public function listByRouter(int $routerId): array;

    /** @param array<string, mixed> $data */
    public function insert(array $data): HotspotProfile;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): HotspotProfile;

    public function delete(int $id): void;

    public function updateSyncStatus(int $id, string $syncStatus): void;
}
