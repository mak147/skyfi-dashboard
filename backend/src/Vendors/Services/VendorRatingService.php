<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Services;

use SkyFi\Vendors\Contracts\VendorRatingRepositoryContract;
use SkyFi\Vendors\DomainModels\VendorRating;
use SkyFi\Vendors\DTOs\VendorRatingData;
use SkyFi\Vendors\Validators\VendorRatingValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class VendorRatingService
{
    public function __construct(
        private readonly VendorRatingRepositoryContract $repository,
        private readonly VendorRatingValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    /** @return array<int, VendorRating> */
    public function list(?int $vendorId = null): array
    {
        return $this->repository->listByVendor($vendorId);
    }

    public function get(int $id): VendorRating
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Rating evaluation not found.');
    }

    public function create(VendorRatingData $data, int $actorId, ?string $ip = null, ?string $agent = null): VendorRating
    {
        $this->validator->validate($data);
        $rating = $this->repository->create($data, $actorId);
        $this->audit->log($actorId, 'vendors.rating.created', 'vendor_rating', $rating->id(), null, $rating->toArray(), $ip, $agent);
        return $this->get($rating->id());
    }
}
