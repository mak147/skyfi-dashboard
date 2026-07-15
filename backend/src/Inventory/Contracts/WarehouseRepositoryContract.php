<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Contracts;

use SkyFi\Inventory\DomainModels\Warehouse;
use SkyFi\Inventory\DTOs\WarehouseData;

interface WarehouseRepositoryContract
{
    /** @return array{items: array<int, Warehouse>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(array $filters): array;
    public function find(int $id): ?Warehouse;
    public function create(WarehouseData $data, int $actorId): Warehouse;
    public function update(int $id, WarehouseData $data, int $actorId): Warehouse;
    public function softDelete(int $id, int $actorId): void;
    /** @return array<int, array<string, mixed>> */
    public function locations(int $warehouseId, bool $includeInactive = true): array;
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function saveLocation(int $warehouseId, ?int $id, array $data): array;
    public function deleteLocation(int $warehouseId, int $id): void;
    public function hasInventory(int $warehouseId, ?int $locationId = null): bool;
}
