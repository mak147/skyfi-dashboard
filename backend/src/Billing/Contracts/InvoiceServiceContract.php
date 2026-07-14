<?php

declare(strict_types=1);

namespace SkyFi\Billing\Contracts;

use SkyFi\Billing\Data\BulkGenerateData;
use SkyFi\Billing\Data\CreateInvoiceData;
use SkyFi\Billing\Data\GenerateInvoiceData;
use SkyFi\Billing\Data\InvoiceListFilters;
use SkyFi\Billing\Data\UpdateInvoiceData;
use SkyFi\Billing\Models\Invoice;

interface InvoiceServiceContract
{
    /**
     * @return array{items: array<Invoice>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(InvoiceListFilters $filters): array;

    public function get(int $id): Invoice;

    public function create(CreateInvoiceData $data, int $authUserId, ?string $ip, ?string $ua): Invoice;

    public function update(int $id, UpdateInvoiceData $data, int $authUserId, ?string $ip, ?string $ua): Invoice;

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void;

    public function changeStatus(int $id, string $status, int $authUserId, ?string $ip, ?string $ua): Invoice;

    public function generate(GenerateInvoiceData $data, int $authUserId, ?string $ip, ?string $ua): Invoice;

    /**
     * @return array{generated: int, failed: int, errors: array<int, array<string, mixed>>}
     */
    public function bulkGenerate(BulkGenerateData $data, int $authUserId, ?string $ip, ?string $ua): array;

    /**
     * @return array<string, int>
     */
    public function statistics(): array;

    public function activity(int $id): array;
}
