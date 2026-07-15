<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class VendorContractData
{
    public function __construct(
        public readonly int $vendorId,
        public readonly string $contractNumber,
        public readonly string $title,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $renewalDate,
        public readonly float $contractValue,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $attachmentPath,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            vendorId: (int) ($data['vendor_id'] ?? 0),
            contractNumber: trim((string) ($data['contract_number'] ?? '')),
            title: trim((string) ($data['title'] ?? '')),
            startDate: trim((string) ($data['start_date'] ?? '')),
            endDate: trim((string) ($data['end_date'] ?? '')),
            renewalDate: isset($data['renewal_date']) && is_string($data['renewal_date']) && $data['renewal_date'] !== '' ? trim($data['renewal_date']) : null,
            contractValue: (float) ($data['contract_value'] ?? 0.0),
            currency: trim((string) ($data['currency'] ?? 'PKR')),
            status: trim((string) ($data['status'] ?? 'active')),
            attachmentPath: isset($data['attachment_path']) && is_string($data['attachment_path']) ? trim($data['attachment_path']) : null,
            notes: isset($data['notes']) && is_string($data['notes']) ? trim($data['notes']) : null,
        );
    }
}
