<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\DTOs;

final class SupplierInvoiceData
{
    public function __construct(
        public readonly string $invoiceNumber,
        public readonly int $vendorId,
        public readonly ?int $purchaseOrderId,
        public readonly string $invoiceDate,
        public readonly ?string $dueDate,
        public readonly float $subtotal,
        public readonly float $taxAmount,
        public readonly float $totalAmount,
        public readonly string $currency,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            invoiceNumber: (string) ($data['invoice_number'] ?? ''),
            vendorId: (int) ($data['vendor_id'] ?? 0),
            purchaseOrderId: isset($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null,
            invoiceDate: (string) ($data['invoice_date'] ?? date('Y-m-d')),
            dueDate: isset($data['due_date']) ? (string) $data['due_date'] : null,
            subtotal: (float) ($data['subtotal'] ?? 0),
            taxAmount: (float) ($data['tax_amount'] ?? 0),
            totalAmount: (float) ($data['total_amount'] ?? 0),
            currency: (string) ($data['currency'] ?? 'PKR'),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }
}
