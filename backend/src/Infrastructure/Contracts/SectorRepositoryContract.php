<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\SectorListFilters;
use SkyFi\Infrastructure\Models\Sector;

interface SectorRepositoryContract
{
    /** Find a sector by ID, or null if not found (including soft-deleted). */
    public function find(int $id): ?Sector;

    /** Find a sector by ID that is not soft-deleted. */
    public function findActive(int $id): ?Sector;

    /**
     * List sectors with filtering, sorting, and pagination.
     *
     * @return array{items: array<Sector>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(SectorListFilters $filters): array;

    /** Create a new sector and return the created model. */
    public function create(array $data): Sector;

    /** Update a sector and return the updated model. */
    public function update(int $id, array $data): Sector;

    /** Soft-delete a sector. */
    public function softDelete(int $id): void;

    /** Update only the status field. */
    public function updateStatus(int $id, string $status): void;

    /** Get sectors by tower. */
    public function getByTower(int $towerId): array;

    /** Get sector with connected connections count. */
    public function getWithConnectionCount(int $id): ?Sector;

    /** Get all active sectors with RF details for coverage map. */
    public function getCoverageData(): array;
}