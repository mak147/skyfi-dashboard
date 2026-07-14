<?php

declare(strict_types=1);

namespace SkyFi\Billing\Contracts;

use SkyFi\Billing\Data\InvoiceListFilters;
use SkyFi\Billing\Models\Invoice;

interface InvoiceRepositoryContract
{
    public function find(int $id): ?Invoice;

    public function findActive(int $id): ?Invoice;

    /**
     * @return array{items: array<Invoice>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(InvoiceListFilters $filters): array;

    public function create(array $data): Invoice;

    public function update(int $id, array $data): Invoice;

    public function softDelete(int $id): void;

    public function updateStatus(int $id, string $status): void;

    public function addItems(int $invoiceId, array $items): void;

    public function getItems(int $invoiceId): array;

    public function deleteItems(int $invoiceId): void;

    public function addActivity(int $invoiceId, string $action, ?string $description, ?int $performedBy): void;

    public function getActivities(int $invoiceId): array;

    public function numberExists(string $number, ?int $excludeId = null): bool;

    /**
     * @return array<string, int>
     */
    public function statistics(): array;
}
