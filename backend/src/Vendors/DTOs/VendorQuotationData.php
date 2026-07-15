<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class VendorQuotationData
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public readonly int $vendorId,
        public readonly ?int $purchaseRequestId,
        public readonly ?string $rfqNumber,
        public readonly string $quotationNumber,
        public readonly string $quotationDate,
        public readonly string $validityDate,
        public readonly float $totalAmount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly array $items,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            vendorId: (int) ($data['vendor_id'] ?? 0),
            purchaseRequestId: isset($data['purchase_request_id']) && is_numeric($data['purchase_request_id']) && ((int) $data['purchase_request_id'] > 0) ? (int) $data['purchase_request_id'] : null,
            rfqNumber: isset($data['rfq_number']) && is_string($data['rfq_number']) ? trim($data['rfq_number']) : null,
            quotationNumber: trim((string) ($data['quotation_number'] ?? '')),
            quotationDate: trim((string) ($data['quotation_date'] ?? '')),
            validityDate: trim((string) ($data['validity_date'] ?? '')),
            totalAmount: (float) ($data['total_amount'] ?? 0.0),
            currency: trim((string) ($data['currency'] ?? 'PKR')),
            status: trim((string) ($data['status'] ?? 'received')),
            notes: isset($data['notes']) && is_string($data['notes']) ? trim($data['notes']) : null,
            items: is_array($data['items'] ?? null) ? $data['items'] : [],
        );
    }
}
