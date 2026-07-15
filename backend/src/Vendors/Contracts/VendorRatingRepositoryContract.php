<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Contracts;

use SkyFi\Vendors\DomainModels\VendorRating;
use SkyFi\Vendors\DTOs\VendorRatingData;

interface VendorRatingRepositoryContract
{
    /** @return array<int, VendorRating> */
    public function listByVendor(?int $vendorId = null): array;
    public function find(int $id): ?VendorRating;
    public function create(VendorRatingData $data, int $actorId): VendorRating;
    public function recalculateOverallRating(int $vendorId): float;
}
