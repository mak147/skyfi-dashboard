<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Contracts;

use SkyFi\Inventory\DomainModels\InventoryAsset;
use SkyFi\Inventory\DTOs\AssetAssignmentData;
use SkyFi\Inventory\DTOs\AssetData;
use SkyFi\Inventory\DTOs\AssetListFilters;

interface AssetRepositoryContract
{
    /** @return array{items: array<int, InventoryAsset>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(AssetListFilters $filters): array;
    public function find(int $id, bool $forUpdate = false): ?InventoryAsset;
    public function create(AssetData $data, int $actorId): InventoryAsset;
    public function update(int $id, AssetData $data, int $actorId): InventoryAsset;
    public function softDelete(int $id, int $actorId): void;
    public function assign(int $id, AssetAssignmentData $data, int $actorId, ?string $status = null): InventoryAsset;
    public function changeStatus(int $id, string $status, int $actorId, ?string $description = null): InventoryAsset;
    /** @return array<int, array<string, mixed>> */
    public function timeline(int $id): array;
    public function transaction(callable $callback): mixed;
}
