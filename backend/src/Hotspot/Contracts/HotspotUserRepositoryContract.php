<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Contracts;

use SkyFi\Hotspot\DomainModels\HotspotUser;
use SkyFi\Hotspot\DTOs\HotspotUserListFilters;

interface HotspotUserRepositoryContract
{
    /** @return array{items: array<int, HotspotUser>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(HotspotUserListFilters $filters): array;

    public function find(int $id): ?HotspotUser;

    public function findByUsername(string $username): ?HotspotUser;

    public function existsByUsername(string $username, ?int $excludeId = null): bool;

    /** @return array<int, HotspotUser> */
    public function listByRouter(int $routerId): array;

    /** @param array<string, mixed> $data */
    public function insert(array $data): HotspotUser;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): HotspotUser;

    public function delete(int $id): void;

    public function updateSyncStatus(int $id, string $syncStatus): void;

    public function countByStatus(?string $status = null): int;

    public function countBySyncStatus(string $syncStatus): int;
}
