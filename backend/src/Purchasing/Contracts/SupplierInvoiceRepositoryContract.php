<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Contracts;

use SkyFi\Purchasing\DomainModels\SupplierInvoice;
use SkyFi\Purchasing\DTOs\SupplierInvoiceData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;

interface SupplierInvoiceRepositoryContract
{
    /** @return array{items: array<int, SupplierInvoice>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(PurchaseOrderListFilters $filters): array;
    public function find(int $id): ?SupplierInvoice;
    public function create(SupplierInvoiceData $data, int $actorId): SupplierInvoice;
    public function update(int $id, SupplierInvoiceData $data, int $actorId): SupplierInvoice;
}
