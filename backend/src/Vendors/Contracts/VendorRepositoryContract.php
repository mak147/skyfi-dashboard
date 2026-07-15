<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Contracts;

use SkyFi\Vendors\DomainModels\Vendor;
use SkyFi\Vendors\DTOs\VendorData;
use SkyFi\Vendors\DTOs\VendorListFilters;

interface VendorRepositoryContract
{
    /** @return array{items: array<int, Vendor>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(VendorListFilters $filters): array;
    public function find(int $id): ?Vendor;
    public function create(VendorData $data, int $actorId): Vendor;
    public function update(int $id, VendorData $data, int $actorId): Vendor;
    public function updateStatus(int $id, string $status, int $actorId): Vendor;
    /** @return array<string, mixed> */
    public function getPurchasingHistory(int $id): array;
}
