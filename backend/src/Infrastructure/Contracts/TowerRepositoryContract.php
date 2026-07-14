<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\TowerListFilters;
use SkyFi\Infrastructure\Models\Tower;

interface TowerRepositoryContract
{
    /** Find a tower by ID, or null if not found (including soft-deleted). */
    public function find(int $id): ?Tower;

    /** Find a tower by ID that is not soft-deleted. */
    public function findActive(int $id): ?Tower;

    /** Check if a tower code already exists. */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /** Check if a tower name already exists within a POP site. */
    public function nameExistsInPopSite(int $popSiteId, string $name, ?int $excludeId = null): bool;

    /**
     * List towers with filtering, sorting, and pagination.
     *
     * @return array{items: array<Tower>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(TowerListFilters $filters): array;

    /** Create a new tower and return the created model. */
    public function create(array $data): Tower;

    /** Update a tower and return the updated model. */
    public function update(int $id, array $data): Tower;

    /** Soft-delete a tower. */
    public function softDelete(int $id): void;

    /** Update only the status field. */
    public function updateStatus(int $id, string $status): void;

    /** Get sectors for a tower. */
    public function getSectorsForTower(int $towerId): array;

    /** Get devices for a tower. */
    public function getDevicesForTower(int $towerId): array;

    /** Get lightweight list for map view. */
    public function getMapPoints(): array;

    /** Get towers by POP site. */
    public function getByPopSite(int $popSiteId): array;
}