<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Contracts;

use SkyFi\Inventory\DomainModels\InventoryProduct;
use SkyFi\Inventory\DTOs\ProductData;
use SkyFi\Inventory\DTOs\ProductListFilters;

interface ProductRepositoryContract
{
    /** @return array{items: array<int, InventoryProduct>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(ProductListFilters $filters): array;
    public function find(int $id, bool $forUpdate = false): ?InventoryProduct;
    public function create(ProductData $data, int $actorId): InventoryProduct;
    public function update(int $id, ProductData $data, int $actorId): InventoryProduct;
    public function softDelete(int $id, int $actorId): void;
    public function existsReference(string $table, int $id): bool;
    /** @return array<int, array<string, mixed>> */
    public function stock(int $warehouseId = 0): array;
}
