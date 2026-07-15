<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class VendorData
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $status,
        public readonly ?string $contactName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $website,
        public readonly ?string $taxId,
        public readonly ?string $registrationNumber,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly string $country,
        public readonly ?string $paymentTerms,
        public readonly string $currency,
        public readonly string $category,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: trim((string) ($data['code'] ?? '')),
            name: trim((string) ($data['name'] ?? '')),
            status: trim((string) ($data['status'] ?? 'active')),
            contactName: isset($data['contact_name']) && is_string($data['contact_name']) ? trim($data['contact_name']) : null,
            email: isset($data['email']) && is_string($data['email']) ? trim($data['email']) : null,
            phone: isset($data['phone']) && is_string($data['phone']) ? trim($data['phone']) : null,
            website: isset($data['website']) && is_string($data['website']) ? trim($data['website']) : null,
            taxId: isset($data['tax_id']) && is_string($data['tax_id']) ? trim($data['tax_id']) : null,
            registrationNumber: isset($data['registration_number']) && is_string($data['registration_number']) ? trim($data['registration_number']) : null,
            address: isset($data['address']) && is_string($data['address']) ? trim($data['address']) : null,
            city: isset($data['city']) && is_string($data['city']) ? trim($data['city']) : null,
            country: trim((string) ($data['country'] ?? 'Pakistan')),
            paymentTerms: isset($data['payment_terms']) && is_string($data['payment_terms']) ? trim($data['payment_terms']) : null,
            currency: trim((string) ($data['currency'] ?? 'PKR')),
            category: trim((string) ($data['category'] ?? 'hardware')),
            notes: isset($data['notes']) && is_string($data['notes']) ? trim($data['notes']) : null,
        );
    }
}
