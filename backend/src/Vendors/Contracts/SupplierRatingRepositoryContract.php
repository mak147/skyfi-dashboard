<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Contracts;
use SkyFi\Vendors\DomainModels\SupplierRating;
use SkyFi\Vendors\DTOs\RatingData;
interface SupplierRatingRepositoryContract
{
    /** @return array<int, SupplierRating> */ public function list(int $vendorId): array;
    public function find(int $id): ?SupplierRating;
    /** @param array<string, float|string|null> $metrics */ public function create(int $vendorId, RatingData $data, array $metrics, int $actorId): SupplierRating;
    /** @param array<string, float|string|null> $metrics */ public function update(int $id, RatingData $data, array $metrics, int $actorId): SupplierRating;
    public function delete(int $id, int $actorId): void;
    public function periodExists(int $vendorId, string $start, string $end, ?int $exceptId = null): bool;
    /** @return array<string, mixed> */ public function performance(int $vendorId, ?string $start = null, ?string $end = null, ?string $currency = null): array;
}
