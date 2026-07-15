<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Contracts;

use SkyFi\Vendors\DomainModels\Supplier;
use SkyFi\Vendors\DTOs\SupplierData;
use SkyFi\Vendors\DTOs\SupplierListFilters;

interface SupplierRepositoryContract
{
    /** @return array{items: array<int, Supplier>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(SupplierListFilters $filters): array;
    public function find(int $id): ?Supplier;
    public function create(SupplierData $data, int $actorId): Supplier;
    public function update(int $id, SupplierData $data, int $actorId): Supplier;
    public function archive(int $id, int $actorId): Supplier;
    public function activate(int $id, int $actorId): Supplier;
    public function updateStatus(int $id, string $status, int $actorId): Supplier;
    public function existsByCode(string $code, ?int $exceptId = null): bool;
    public function existsByName(string $name, ?int $exceptId = null): bool;
    /** @param array<int, int> $categoryIds */
    public function syncCategories(int $vendorId, array $categoryIds, int $actorId): void;
    /** @return array<int, array<string, mixed>> */
    public function categories(bool $activeOnly = false): array;
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createCategory(array $data, int $actorId): array;
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function updateCategory(int $id, array $data, int $actorId): array;
    public function deleteCategory(int $id, int $actorId): void;
    /** @return array<int, array<string, mixed>> */
    public function purchaseOrders(int $vendorId, int $limit = 100): array;
    /** @return array<int, array<string, mixed>> */
    public function products(int $vendorId): array;
    /** @return array<string, mixed> */
    public function financialReferences(int $vendorId): array;
}
