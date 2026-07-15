<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class QuotationData
{
    /** @param array<int, array<string, mixed>> $items */
    public function __construct(
        public readonly string $quotationNumber,
        public readonly ?string $rfqReference,
        public readonly string $quotationDate,
        public readonly ?string $validUntil,
        public readonly string $currency,
        public readonly float $taxAmount,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly array $items,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $text = static fn(string $key): ?string => isset($data[$key]) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;
        return new self(
            trim((string) ($data['quotation_number'] ?? '')),
            $text('rfq_reference'),
            trim((string) ($data['quotation_date'] ?? date('Y-m-d'))),
            $text('valid_until'),
            strtoupper(trim((string) ($data['currency'] ?? 'PKR'))),
            (float) ($data['tax_amount'] ?? 0),
            trim((string) ($data['status'] ?? 'received')),
            $text('notes'),
            is_array($data['items'] ?? null) ? array_values($data['items']) : [],
        );
    }
}
