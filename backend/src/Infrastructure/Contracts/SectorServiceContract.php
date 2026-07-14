<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\CreateSectorData;
use SkyFi\Infrastructure\Data\SectorListFilters;
use SkyFi\Infrastructure\Data\UpdateSectorData;
use SkyFi\Infrastructure\Models\Sector;

interface SectorServiceContract
{
    /** List sectors with filtering, sorting, and pagination. */
    public function list(SectorListFilters $filters): array;

    /** Get a sector by ID. */
    public function get(int $id): Sector;

    /** Create a new sector. */
    public function create(CreateSectorData $data, int $authUserId, ?string $ip, ?string $ua): Sector;

    /** Update a sector. */
    public function update(int $id, UpdateSectorData $data, int $authUserId, ?string $ip, ?string $ua): Sector;

    /** Soft delete a sector. */
    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void;

    /** Change sector status. */
    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Sector;

    /** Get sectors by tower. */
    public function getByTower(int $towerId): array;

    /** Get sector with connected connections count. */
    public function getWithConnectionCount(int $id): Sector;

    /** Get all active sectors with RF details for coverage map. */
    public function getCoverageData(): array;
}