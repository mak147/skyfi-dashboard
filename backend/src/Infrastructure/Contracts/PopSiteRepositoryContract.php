<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\PopSiteListFilters;
use SkyFi\Infrastructure\Models\PopSite;

interface PopSiteRepositoryContract
{
    /** Find a POP site by ID, or null if not found (including soft-deleted). */
    public function find(int $id): ?PopSite;

    /** Find a POP site by ID that is not soft-deleted. */
    public function findActive(int $id): ?PopSite;

    /** Check if a POP site code already exists. */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /** Check if a POP site name already exists. */
    public function nameExists(string $name, ?int $excludeId = null): bool;

    /**
     * List POP sites with filtering, sorting, and pagination.
     *
     * @return array{items: array<PopSite>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(PopSiteListFilters $filters): array;

    /** Create a new POP site and return the created model. */
    public function create(array $data): PopSite;

    /** Update a POP site and return the updated model. */
    public function update(int $id, array $data): PopSite;

    /** Soft-delete a POP site. */
    public function softDelete(int $id): void;

    /** Update only the status field. */
    public function updateStatus(int $id, string $status): void;

    /** Get towers for a POP site (lightweight). */
    public function getTowersForPopSite(int $popSiteId): array;

    /** Get lightweight list for map view. */
    public function getMapPoints(): array;
}