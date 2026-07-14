<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\CreateTowerData;
use SkyFi\Infrastructure\Data\TowerListFilters;
use SkyFi\Infrastructure\Data\UpdateTowerData;
use SkyFi\Infrastructure\Models\Tower;

interface TowerServiceContract
{
    /** List towers with filtering, sorting, and pagination. */
    public function list(TowerListFilters $filters): array;

    /** Get a tower by ID. */
    public function get(int $id): Tower;

    /** Create a new tower. */
    public function create(CreateTowerData $data, int $authUserId, ?string $ip, ?string $ua): Tower;

    /** Update a tower. */
    public function update(int $id, UpdateTowerData $data, int $authUserId, ?string $ip, ?string $ua): Tower;

    /** Soft delete a tower. */
    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void;

    /** Change tower status. */
    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Tower;

    /** Get sectors for a tower. */
    public function getSectors(int $towerId): array;

    /** Get devices for a tower. */
    public function getDevices(int $towerId): array;

    /** Get map points for all towers. */
    public function getMapPoints(): array;

    /** Get towers by POP site. */
    public function getByPopSite(int $popSiteId): array;
}