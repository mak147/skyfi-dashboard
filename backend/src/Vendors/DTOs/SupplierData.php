<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class SupplierData
{
    /** @param array<int, int> $categoryIds */
    public function __construct(
        public readonly string $supplierCode,
        public readonly string $companyName,
        public readonly ?string $taxNumber,
        public readonly ?string $registrationNumber,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $country,
        public readonly ?string $contactPerson,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $website,
        public readonly ?string $paymentTerms,
        public readonly string $currency,
        public readonly ?string $notes,
        public readonly string $status,
        public readonly array $categoryIds,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $text = static fn(string $key): ?string => isset($data[$key]) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;
        $categories = is_array($data['category_ids'] ?? null) ? array_values(array_unique(array_filter(array_map('intval', $data['category_ids']), static fn(int $id): bool => $id > 0))) : [];

        return new self(
            strtoupper(trim((string) ($data['supplier_code'] ?? $data['code'] ?? ''))),
            trim((string) ($data['company_name'] ?? $data['name'] ?? '')),
            $text('tax_number') ?? $text('tax_id'),
            $text('registration_number'),
            $text('address'),
            $text('city'),
            $text('country'),
            $text('contact_person') ?? $text('contact_name'),
            $text('phone'),
            $text('email'),
            $text('website'),
            $text('payment_terms'),
            strtoupper(trim((string) ($data['currency'] ?? 'PKR'))),
            $text('notes'),
            trim((string) ($data['status'] ?? 'active')),
            $categories,
        );
    }
}
