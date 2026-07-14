<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\HotspotProfile;
use SkyFi\Hotspot\DTOs\CreateHotspotProfileData;
use SkyFi\Hotspot\DTOs\HotspotProfileListFilters;
use SkyFi\Hotspot\DTOs\UpdateHotspotProfileData;

interface HotspotProfileServiceContract
{
    /** @return array{items: array<int, HotspotProfile>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(HotspotProfileListFilters $filters): array;

    public function get(int $id): HotspotProfile;

    public function create(CreateHotspotProfileData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotProfile;

    public function update(int $id, UpdateHotspotProfileData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotProfile;

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void;

    /** @return array<int, array<string, mixed>> */
    public function fetchRouterProfiles(int $routerId): array;
}
