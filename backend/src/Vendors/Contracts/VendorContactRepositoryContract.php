<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Contracts;

use SkyFi\Vendors\DomainModels\VendorContact;
use SkyFi\Vendors\DTOs\VendorContactData;

interface VendorContactRepositoryContract
{
    /** @return array<int, VendorContact> */
    public function listByVendor(?int $vendorId = null): array;
    public function find(int $id): ?VendorContact;
    public function create(VendorContactData $data, int $actorId): VendorContact;
    public function update(int $id, VendorContactData $data, int $actorId): VendorContact;
    public function delete(int $id, int $actorId): void;
}
