<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Services;

use SkyFi\Vendors\Contracts\VendorContractRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorContract;
use SkyFi\Vendors\DTOs\VendorContractData;
use SkyFi\Vendors\Validators\VendorContractValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class VendorContractService
{
    public function __construct(
        private readonly VendorContractRepositoryContract $repository,
        private readonly VendorContractValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    /** @return array<int, VendorContract> */
    public function list(?int $vendorId = null): array
    {
        return $this->repository->listByVendor($vendorId);
    }

    public function get(int $id): VendorContract
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Contract not found.');
    }

    public function create(VendorContractData $data, int $actorId, ?string $ip = null, ?string $agent = null): VendorContract
    {
        $this->validator->validate($data);
        $contract = $this->repository->create($data, $actorId);
        $this->audit->log($actorId, 'vendors.contract.created', 'vendor_contract', $contract->id(), null, $contract->toArray(), $ip, $agent);
        return $this->get($contract->id());
    }

    public function update(int $id, VendorContractData $data, int $actorId, ?string $ip = null, ?string $agent = null): VendorContract
    {
        $existing = $this->get($id);
        $this->validator->validate($data);
        $old = $existing->toArray();
        $contract = $this->repository->update($id, $data, $actorId);
        $this->audit->log($actorId, 'vendors.contract.updated', 'vendor_contract', $id, $old, $contract->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function delete(int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $existing = $this->get($id);
        $this->repository->delete($id, $actorId);
        $this->audit->log($actorId, 'vendors.contract.deleted', 'vendor_contract', $id, $existing->toArray(), null, $ip, $agent);
    }
}
