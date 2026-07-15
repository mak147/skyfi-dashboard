<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class ContractData
{
    public function __construct(
        public readonly string $contractNumber,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $renewalDate,
        public readonly float $contractValue,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $attachmentName,
        public readonly ?string $attachmentReference,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $text = static fn(string $key): ?string => isset($data[$key]) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;
        return new self(
            trim((string) ($data['contract_number'] ?? '')),
            trim((string) ($data['start_date'] ?? '')),
            trim((string) ($data['end_date'] ?? '')),
            $text('renewal_date'),
            (float) ($data['contract_value'] ?? 0),
            strtoupper(trim((string) ($data['currency'] ?? 'PKR'))),
            trim((string) ($data['status'] ?? 'draft')),
            $text('attachment_name'),
            $text('attachment_reference'),
            $text('notes'),
        );
    }
}
