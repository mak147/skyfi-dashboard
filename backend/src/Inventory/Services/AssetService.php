<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Services;

use SkyFi\Inventory\Contracts\AssetRepositoryContract;
use SkyFi\Inventory\Contracts\ProductRepositoryContract;
use SkyFi\Inventory\DomainModels\InventoryAsset;
use SkyFi\Inventory\DTOs\AssetAssignmentData;
use SkyFi\Inventory\DTOs\AssetData;
use SkyFi\Inventory\DTOs\AssetListFilters;
use SkyFi\Inventory\Validators\AssetValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class AssetService
{
    public function __construct(
        private readonly AssetRepositoryContract $repository,
        private readonly ProductRepositoryContract $products,
        private readonly AssetValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(AssetListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): InventoryAsset
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Inventory asset not found.');
    }

    public function create(AssetData $data, int $actorId, ?string $ip = null, ?string $agent = null): InventoryAsset
    {
        $this->validator->validate($data);
        $product = $this->products->find($data->productId) ?? throw new NotFoundException('Inventory product not found.');
        if ($product->trackingMode() !== 'serialized') {
            throw new ValidationException([['code' => 'tracking_mode_mismatch', 'detail' => 'Assets can only be created for serialized products.', 'source' => ['pointer' => '/data/attributes/product_id']]]);
        }
        try {
            $asset = $this->repository->transaction(function () use ($data, $actorId): InventoryAsset {
                $created = $this->repository->create($data, $actorId);
                if ($data->initialAssignment !== null) {
                    return $this->repository->assign($created->id(), $data->initialAssignment, $actorId, $data->status === 'in_stock' ? null : $data->status);
                }
                return $created;
            });
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'Asset tag, serial number, MAC, IMEI, or barcode already exists, or a reference is invalid.']]);
        }
        $this->audit->log($actorId, 'inventory.asset.created', 'inventory_asset', $asset->id(), null, $asset->toArray(), $ip, $agent);
        return $asset;
    }

    public function update(int $id, AssetData $data, int $actorId, ?string $ip = null, ?string $agent = null): InventoryAsset
    {
        $old = $this->get($id);
        if (in_array($old->status(), ['scrapped', 'retired'], true)) {
            throw new ValidationException([['code' => 'asset_terminal_state', 'detail' => 'Scrapped or retired assets cannot be edited.']]);
        }
        $this->validator->validate($data);
        $product = $this->products->find($data->productId) ?? throw new NotFoundException('Inventory product not found.');
        if ($product->trackingMode() !== 'serialized') {
            throw new ValidationException([['code' => 'tracking_mode_mismatch', 'detail' => 'Assets can only reference serialized products.']]);
        }
        try {
            $asset = $this->repository->update($id, $data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'Asset tag, serial number, MAC, IMEI, or barcode already exists, or a reference is invalid.']]);
        }
        $this->audit->log($actorId, 'inventory.asset.updated', 'inventory_asset', $id, $old->toArray(), $asset->toArray(), $ip, $agent);
        return $asset;
    }

    public function delete(int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $asset = $this->get($id);
        $attributes = $asset->toArray();
        if (($attributes['current_assignment_id'] ?? null) !== null && ($attributes['assignment_type'] ?? null) !== 'warehouse') {
            throw new ValidationException([['code' => 'asset_assigned', 'detail' => 'Return the asset to a warehouse before retiring it.']]);
        }
        $this->repository->softDelete($id, $actorId);
        $this->audit->log($actorId, 'inventory.asset.deleted', 'inventory_asset', $id, $attributes, null, $ip, $agent);
    }

    public function assign(int $id, AssetAssignmentData $data, int $actorId, ?string $ip = null, ?string $agent = null): InventoryAsset
    {
        $old = $this->get($id);
        if (in_array($old->status(), ['scrapped', 'retired', 'lost'], true)) {
            throw new ValidationException([['code' => 'asset_unavailable', 'detail' => 'This asset cannot be assigned in its current state.']]);
        }
        $this->validator->validateAssignment($data);
        try {
            $asset = $this->repository->transaction(fn(): InventoryAsset => $this->repository->assign($id, $data, $actorId));
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'invalid_assignment_target', 'detail' => 'The selected assignment target does not exist or is unavailable.']]);
        }
        $this->audit->log($actorId, 'inventory.asset.assigned', 'inventory_asset', $id, $old->toArray(), $asset->toArray(), $ip, $agent);
        return $asset;
    }

    public function returnToWarehouse(int $id, int $locationId, ?string $notes, int $actorId, ?string $ip = null, ?string $agent = null): InventoryAsset
    {
        $data = new AssetAssignmentData('warehouse', $locationId, null, null, null, null, $notes);
        $this->validator->validateAssignment($data);
        $old = $this->get($id);
        try {
            $asset = $this->repository->transaction(fn(): InventoryAsset => $this->repository->assign($id, $data, $actorId, 'returned'));
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'invalid_warehouse_location', 'detail' => 'The selected warehouse location is invalid.']]);
        }
        $this->audit->log($actorId, 'inventory.asset.returned', 'inventory_asset', $id, $old->toArray(), $asset->toArray(), $ip, $agent);
        return $asset;
    }

    public function changeStatus(int $id, string $status, ?string $reason, int $actorId, ?string $ip = null, ?string $agent = null): InventoryAsset
    {
        $this->validator->validateStatus($status);
        $old = $this->get($id);
        $asset = $this->repository->transaction(fn(): InventoryAsset => $this->repository->changeStatus($id, $status, $actorId, $reason));
        $this->audit->log($actorId, 'inventory.asset.status_changed', 'inventory_asset', $id, $old->toArray(), $asset->toArray(), $ip, $agent);
        return $asset;
    }

    public function timeline(int $id): array
    {
        $this->get($id);
        return $this->repository->timeline($id);
    }
}
