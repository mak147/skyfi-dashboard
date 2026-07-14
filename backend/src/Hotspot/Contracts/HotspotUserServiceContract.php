<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\HotspotUser;
use SkyFi\Hotspot\DTOs\BulkImportUserData;
use SkyFi\Hotspot\DTOs\CreateHotspotUserData;
use SkyFi\Hotspot\DTOs\HotspotUserListFilters;
use SkyFi\Hotspot\DTOs\UpdateHotspotUserData;

interface HotspotUserServiceContract
{
    /** @return array{items: array<int, HotspotUser>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(HotspotUserListFilters $filters): array;

    public function get(int $id): HotspotUser;

    public function create(CreateHotspotUserData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    public function update(int $id, UpdateHotspotUserData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void;

    public function setEnabled(int $id, bool $isEnabled, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    public function suspend(int $id, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    public function resume(int $id, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    public function resetPassword(int $id, string $newPassword, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    public function assignProfile(int $id, int $profileId, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    public function assignRouter(int $id, int $routerId, int $actorId, ?string $ip, ?string $userAgent): HotspotUser;

    /** @return array{imported_count: int, failed_count: int, errors: array<int, string>} */
    public function bulkImport(BulkImportUserData $data, int $actorId, ?string $ip, ?string $userAgent): array;
}
