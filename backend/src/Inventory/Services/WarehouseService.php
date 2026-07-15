<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Services;

use SkyFi\Inventory\Contracts\WarehouseRepositoryContract;
use SkyFi\Inventory\DomainModels\Warehouse;
use SkyFi\Inventory\DTOs\WarehouseData;
use SkyFi\Inventory\Validators\WarehouseValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryContract $repository,
        private readonly WarehouseValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(array $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): Warehouse
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Warehouse not found.');
    }

    public function create(WarehouseData $data, int $actorId, ?string $ip = null, ?string $agent = null): Warehouse
    {
        $this->validator->validate($data);
        try {
            $warehouse = $this->repository->create($data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'Warehouse code or name already exists, or the manager is invalid.']]);
        }
        $this->audit->log($actorId, 'inventory.warehouse.created', 'warehouse', $warehouse->id(), null, $warehouse->toArray(), $ip, $agent);
        return $warehouse;
    }

    public function update(int $id, WarehouseData $data, int $actorId, ?string $ip = null, ?string $agent = null): Warehouse
    {
        $old = $this->get($id);
        $this->validator->validate($data);
        if (in_array($data->status, ['inactive', 'closed'], true) && $this->repository->hasInventory($id)) {
            throw new ValidationException([['code' => 'warehouse_not_empty', 'detail' => 'A warehouse with stock or assigned assets cannot be closed or deactivated.']]);
        }
        try {
            $warehouse = $this->repository->update($id, $data, $actorId);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'Warehouse code or name already exists, or a reference is invalid.']]);
        }
        $this->audit->log($actorId, 'inventory.warehouse.updated', 'warehouse', $id, $old->toArray(), $warehouse->toArray(), $ip, $agent);
        return $warehouse;
    }

    public function delete(int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $warehouse = $this->get($id);
        if ($this->repository->hasInventory($id)) {
            throw new ValidationException([['code' => 'warehouse_not_empty', 'detail' => 'A warehouse with stock or assigned assets cannot be deleted.']]);
        }
        $this->repository->softDelete($id, $actorId);
        $this->audit->log($actorId, 'inventory.warehouse.deleted', 'warehouse', $id, $warehouse->toArray(), null, $ip, $agent);
    }

    public function locations(int $warehouseId): array
    {
        $this->get($warehouseId);
        return $this->repository->locations($warehouseId);
    }

    public function saveLocation(int $warehouseId, ?int $id, array $data, int $actorId, ?string $ip = null, ?string $agent = null): array
    {
        $this->get($warehouseId);
        $this->validator->validateLocation($data);
        if ($id !== null && in_array((string) ($data['status'] ?? 'active'), ['inactive'], true) && $this->repository->hasInventory($warehouseId, $id)) {
            throw new ValidationException([['code' => 'location_not_empty', 'detail' => 'A location with stock or assigned assets cannot be deactivated.']]);
        }
        try {
            $location = $this->repository->saveLocation($warehouseId, $id, $data);
        } catch (\PDOException $exception) {
            throw new ValidationException([['code' => 'duplicate_or_invalid_reference', 'detail' => 'Location code already exists in this warehouse, or its parent is invalid.']]);
        }
        $this->audit->log($actorId, $id === null ? 'inventory.location.created' : 'inventory.location.updated', 'warehouse_location', (int) ($location['id'] ?? 0), null, $location, $ip, $agent);
        return $location;
    }

    public function deleteLocation(int $warehouseId, int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        if ($this->repository->hasInventory($warehouseId, $id)) {
            throw new ValidationException([['code' => 'location_not_empty', 'detail' => 'A location with stock or assigned assets cannot be removed.']]);
        }
        $this->repository->deleteLocation($warehouseId, $id);
        $this->audit->log($actorId, 'inventory.location.deleted', 'warehouse_location', $id, null, null, $ip, $agent);
    }
}
