<?php

declare(strict_types=1);
namespace SkyFi\Packages\Contracts;
use SkyFi\Packages\Data\PackageListFilters;
use SkyFi\Packages\Models\InternetPackage;
interface PackageRepositoryContract
{
    public function find(int $id): ?InternetPackage;
    public function codeExists(string $code, ?int $excludeId = null): bool;
    /** @return array{items:array<InternetPackage>,total:int,page:int,perPage:int,lastPage:int} */ public function list(
        PackageListFilters $filters,
    ): array;
    public function create(array $data, int $userId): InternetPackage;
    public function update(int $id, array $data, int $userId): InternetPackage;
    public function changeStatus(
        int $id,
        string $status,
        int $userId,
    ): InternetPackage;
    public function softDelete(int $id): void;
    public function isInUse(int $id): bool;
    /** @return array<string,mixed> */ public function statistics(): array;
    /** @return array<int,array<string,mixed>> */ public function activity(
        int $id,
    ): array;
    public function getPrice(int $id, string $billingPeriod): float;
}
