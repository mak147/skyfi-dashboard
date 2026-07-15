<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Services;

use SkyFi\Vendors\Contracts\VendorContactRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorContact;
use SkyFi\Vendors\DTOs\VendorContactData;
use SkyFi\Vendors\Validators\VendorContactValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class VendorContactService
{
    public function __construct(
        private readonly VendorContactRepositoryContract $repository,
        private readonly VendorContactValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    /** @return array<int, VendorContact> */
    public function list(?int $vendorId = null): array
    {
        return $this->repository->listByVendor($vendorId);
    }

    public function get(int $id): VendorContact
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Contact not found.');
    }

    public function create(VendorContactData $data, int $actorId, ?string $ip = null, ?string $agent = null): VendorContact
    {
        $this->validator->validate($data);
        $contact = $this->repository->create($data, $actorId);
        $this->audit->log($actorId, 'vendors.contact.created', 'vendor_contact', $contact->id(), null, $contact->toArray(), $ip, $agent);
        return $this->get($contact->id());
    }

    public function update(int $id, VendorContactData $data, int $actorId, ?string $ip = null, ?string $agent = null): VendorContact
    {
        $existing = $this->get($id);
        $this->validator->validate($data);
        $old = $existing->toArray();
        $contact = $this->repository->update($id, $data, $actorId);
        $this->audit->log($actorId, 'vendors.contact.updated', 'vendor_contact', $id, $old, $contact->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function delete(int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $existing = $this->get($id);
        $this->repository->delete($id, $actorId);
        $this->audit->log($actorId, 'vendors.contact.deleted', 'vendor_contact', $id, $existing->toArray(), null, $ip, $agent);
    }
}
