<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Contracts;

use SkyFi\Vendors\DomainModels\VendorContract;
use SkyFi\Vendors\DTOs\VendorContractData;

interface VendorContractRepositoryContract
{
    /** @return array<int, VendorContract> */
    public function listByVendor(?int $vendorId = null): array;
    public function find(int $id): ?VendorContract;
    public function create(VendorContractData $data, int $actorId): VendorContract;
    public function update(int $id, VendorContractData $data, int $actorId): VendorContract;
    public function delete(int $id, int $actorId): void;
}
