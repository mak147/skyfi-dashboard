<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Contracts;

use SkyFi\Vendors\DomainModels\VendorQuotation;
use SkyFi\Vendors\DTOs\VendorQuotationData;

interface VendorQuotationRepositoryContract
{
    /** @return array<int, VendorQuotation> */
    public function listByVendor(?int $vendorId = null): array;
    public function find(int $id): ?VendorQuotation;
    public function create(VendorQuotationData $data, int $actorId): VendorQuotation;
    public function updateStatus(int $id, string $status, int $actorId): VendorQuotation;
    public function delete(int $id, int $actorId): void;
}
