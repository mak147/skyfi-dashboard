<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Services;

use SkyFi\Inventory\Contracts\ProductRepositoryContract;
use SkyFi\Inventory\DomainModels\InventoryProduct;
use SkyFi\Inventory\DTOs\ProductData;
use SkyFi\Inventory\DTOs\ProductListFilters;
use SkyFi\Inventory\Validators\ProductValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryContract $repository,
        private readonly ProductValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(ProductListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): InventoryProduct
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Inventory product not found.');
    }

    public function create(ProductData $data, int $actorId, ?string $ip = null, ?string $agent = null): InventoryProduct
    {
        $this->validator->validate($data);
        try {
            $product = $this->repository->create($data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'SKU or barcode already exists, or a catalog reference is invalid.']]);
        }
        $this->audit->log($actorId, 'inventory.product.created', 'inventory_product', $product->id(), null, $product->toArray(), $ip, $agent);
        return $product;
    }

    public function update(int $id, ProductData $data, int $actorId, ?string $ip = null, ?string $agent = null): InventoryProduct
    {
        $old = $this->get($id);
        $this->validator->validate($data);
        if ($old->trackingMode() !== $data->trackingMode) {
            foreach (['inventory_assets', 'inventory_stock_balances', 'inventory_stock_movement_lines'] as $table) {
                if ($this->repository->existsReference($table, $id)) {
                    throw new ValidationException([['code' => 'tracking_mode_locked', 'detail' => 'Tracking mode cannot change after inventory activity exists.', 'source' => ['pointer' => '/data/attributes/tracking_mode']]]);
                }
            }
        }
        try {
            $product = $this->repository->update($id, $data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'SKU or barcode already exists, or a catalog reference is invalid.']]);
        }
        $this->audit->log($actorId, 'inventory.product.updated', 'inventory_product', $id, $old->toArray(), $product->toArray(), $ip, $agent);
        return $product;
    }

    public function delete(int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $product = $this->get($id);
        foreach (['inventory_assets', 'inventory_stock_balances', 'inventory_stock_movement_lines', 'inventory_warehouse_transfer_lines'] as $table) {
            if ($this->repository->existsReference($table, $id)) {
                throw new ValidationException([['code' => 'product_in_use', 'detail' => 'Products with stock, assets, transfers, or movement history cannot be deleted; set the product inactive instead.']]);
            }
        }
        $this->repository->softDelete($id, $actorId);
        $this->audit->log($actorId, 'inventory.product.deleted', 'inventory_product', $id, $product->toArray(), null, $ip, $agent);
    }

    public function stock(int $warehouseId = 0): array
    {
        return $this->repository->stock($warehouseId);
    }
}
