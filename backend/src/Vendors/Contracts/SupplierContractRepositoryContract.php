<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Contracts;
use SkyFi\Vendors\DomainModels\SupplierContract;
use SkyFi\Vendors\DTOs\ContractData;
use SkyFi\Vendors\DTOs\ContractListFilters;
interface SupplierContractRepositoryContract
{
    /** @return array{items: array<int, SupplierContract>, total: int, page: int, perPage: int, lastPage: int} */ public function list(ContractListFilters $filters): array;
    public function find(int $id): ?SupplierContract;
    public function create(int $vendorId, ContractData $data, int $actorId): SupplierContract;
    public function update(int $id, ContractData $data, int $actorId): SupplierContract;
    public function delete(int $id, int $actorId): void;
    public function numberExists(string $number, ?int $exceptId = null): bool;
}
