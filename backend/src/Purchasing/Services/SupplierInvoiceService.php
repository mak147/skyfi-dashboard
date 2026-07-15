<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Services;

use SkyFi\Purchasing\Contracts\SupplierInvoiceRepositoryContract;
use SkyFi\Purchasing\DomainModels\SupplierInvoice;
use SkyFi\Purchasing\DTOs\SupplierInvoiceData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;
use SkyFi\Purchasing\Validators\SupplierInvoiceValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class SupplierInvoiceService
{
    public function __construct(
        private readonly SupplierInvoiceRepositoryContract $repository,
        private readonly SupplierInvoiceValidator $validator,
        private readonly AuditLoggerContract $audit,
    ) {
    }

    public function list(PurchaseOrderListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): SupplierInvoice
    {
        return $this->repository->find($id) ?? throw new NotFoundException('Supplier invoice not found.');
    }

    public function create(SupplierInvoiceData $data, int $actorId, ?string $ip = null, ?string $agent = null): SupplierInvoice
    {
        $this->validator->validate($data);
        try {
            $invoice = $this->repository->create($data, $actorId);
        } catch (\PDOException $e) {
            throw new ValidationException([['code' => 'duplicate_invoice', 'detail' => 'This invoice number already exists for this supplier.']]);
        }
        $this->audit->log($actorId, 'purchasing.invoice.registered', 'supplier_invoice', $invoice->id(), null, $invoice->toArray(), $ip, $agent);
        EventDispatcher::dispatch('purchasing.invoice.registered', $invoice->toArray());
        return $this->get($invoice->id());
    }

    public function update(int $id, SupplierInvoiceData $data, int $actorId, ?string $ip = null, ?string $agent = null): SupplierInvoice
    {
        $existing = $this->get($id);
        $existingData = $existing->toArray();
        if (!in_array($existingData['status'] ?? '', ['draft', 'registered'], true)) {
            throw new ValidationException([['code' => 'invalid_status', 'detail' => 'Only draft or registered invoices can be edited.']]);
        }
        $this->validator->validate($data);
        $old = $existing->toArray();
        $invoice = $this->repository->update($id, $data, $actorId);
        $this->audit->log($actorId, 'purchasing.invoice.updated', 'supplier_invoice', $id, $old, $invoice->toArray(), $ip, $agent);
        return $this->get($id);
    }
}
