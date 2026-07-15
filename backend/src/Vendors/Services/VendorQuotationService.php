<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Services;

use SkyFi\Vendors\Contracts\VendorQuotationRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorQuotation;
use SkyFi\Vendors\DTOs\VendorQuotationData;
use SkyFi\Vendors\Validators\VendorQuotationValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class VendorQuotationService
{
    public function __construct(
        private readonly VendorQuotationRepositoryContract $repository,
        private readonly VendorQuotationValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    /** @return array<int, VendorQuotation> */
    public function list(?int $vendorId = null): array
    {
        return $this->repository->listByVendor($vendorId);
    }

    public function get(int $id): VendorQuotation
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Quotation not found.');
    }

    public function create(VendorQuotationData $data, int $actorId, ?string $ip = null, ?string $agent = null): VendorQuotation
    {
        $this->validator->validate($data);
        $quotation = $this->repository->create($data, $actorId);
        $this->audit->log($actorId, 'vendors.quotation.created', 'vendor_quotation', $quotation->id(), null, $quotation->toArray(), $ip, $agent);
        return $this->get($quotation->id());
    }

    public function updateStatus(int $id, string $status, int $actorId, ?string $ip = null, ?string $agent = null): VendorQuotation
    {
        $existing = $this->get($id);
        if (!in_array($status, ['received', 'under_review', 'accepted', 'rejected', 'expired'], true)) {
            throw new \InvalidArgumentException('Invalid quotation status.');
        }
        $old = $existing->toArray();
        $quotation = $this->repository->updateStatus($id, $status, $actorId);
        $this->audit->log($actorId, 'vendors.quotation.status_updated', 'vendor_quotation', $id, $old, $quotation->toArray(), $ip, $agent);
        return $this->get($id);
    }

    public function delete(int $id, int $actorId, ?string $ip = null, ?string $agent = null): void
    {
        $existing = $this->get($id);
        $this->repository->delete($id, $actorId);
        $this->audit->log($actorId, 'vendors.quotation.deleted', 'vendor_quotation', $id, $existing->toArray(), null, $ip, $agent);
    }
}
