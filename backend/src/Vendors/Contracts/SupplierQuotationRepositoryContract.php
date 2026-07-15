<?php

declare(strict_types=1);
namespace SkyFi\Vendors\Contracts;
use SkyFi\Vendors\DomainModels\SupplierQuotation;
use SkyFi\Vendors\DTOs\QuotationData;
use SkyFi\Vendors\DTOs\QuotationListFilters;
interface SupplierQuotationRepositoryContract
{
    /** @return array{items: array<int, SupplierQuotation>, total: int, page: int, perPage: int, lastPage: int} */ public function list(QuotationListFilters $filters): array;
    public function find(int $id): ?SupplierQuotation;
    public function create(int $vendorId, QuotationData $data, int $actorId): SupplierQuotation;
    public function update(int $id, QuotationData $data, int $actorId): SupplierQuotation;
    public function delete(int $id, int $actorId): void;
    public function numberExists(int $vendorId, string $number, ?int $exceptId = null): bool;
    /** @return array<int, array<string, mixed>> */ public function compare(string $rfqReference, ?int $productId = null): array;
    /** @return array<int, array<string, mixed>> */ public function history(int $quotationId): array;
}
