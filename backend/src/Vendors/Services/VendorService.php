<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Services;

use SkyFi\Vendors\Contracts\VendorRepositoryContract;
use SkyFi\Vendors\DomainModels\Vendor;
use SkyFi\Vendors\DTOs\VendorData;
use SkyFi\Vendors\DTOs\VendorListFilters;
use SkyFi\Vendors\Validators\VendorValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class VendorService
{
    public function __construct(
        private readonly VendorRepositoryContract $repository,
        private readonly VendorValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(VendorListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): Vendor
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Supplier not found.');
    }

    public function create(VendorData $data, int $actorId, ?string $ip = null, ?string $agent = null): Vendor
    {
        $this->validator->validate($data);
        $vendor = $this->repository->create($data, $actorId);
        $this->audit->log($actorId, 'vendors.supplier.created', 'vendor', $vendor->id(), null, $vendor->toArray(), $ip, $agent);
        return $this->get($vendor->id());
    }

    public function update(int $id, VendorData $data, int $actorId, ?string $ip = null, ?string $agent = null): Vendor
    {
        $existing = $this->get($id);
        $this->validator->validate($data);
        $old = $existing->toArray();
        $vendor = $this->repository->update($id, $data, $actorId);
        $this->audit->log($actorId, 'vendors.supplier.updated', 'vendor', $id, $old, $vendor->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function archive(int $id, int $actorId, ?string $ip = null, ?string $agent = null): Vendor
    {
        $existing = $this->get($id);
        $old = $existing->toArray();
        $vendor = $this->repository->updateStatus($id, 'inactive', $actorId);
        $this->audit->log($actorId, 'vendors.supplier.archived', 'vendor', $id, $old, $vendor->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function activate(int $id, int $actorId, ?string $ip = null, ?string $agent = null): Vendor
    {
        $existing = $this->get($id);
        $old = $existing->toArray();
        $vendor = $this->repository->updateStatus($id, 'active', $actorId);
        $this->audit->log($actorId, 'vendors.supplier.activated', 'vendor', $id, $old, $vendor->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function getPurchasingHistory(int $id): array
    {
        $this->get($id);
        return $this->repository->getPurchasingHistory($id);
    }
}
