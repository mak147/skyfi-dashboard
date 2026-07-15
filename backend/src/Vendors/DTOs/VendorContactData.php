<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class VendorContactData
{
    public function __construct(
        public readonly int $vendorId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $department,
        public readonly ?string $position,
        public readonly bool $isPrimary,
        public readonly bool $isEmergency,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            vendorId: (int) ($data['vendor_id'] ?? 0),
            firstName: trim((string) ($data['first_name'] ?? '')),
            lastName: trim((string) ($data['last_name'] ?? '')),
            email: isset($data['email']) && is_string($data['email']) ? trim($data['email']) : null,
            phone: isset($data['phone']) && is_string($data['phone']) ? trim($data['phone']) : null,
            department: isset($data['department']) && is_string($data['department']) ? trim($data['department']) : null,
            position: isset($data['position']) && is_string($data['position']) ? trim($data['position']) : null,
            isPrimary: !empty($data['is_primary']),
            isEmergency: !empty($data['is_emergency']),
            notes: isset($data['notes']) && is_string($data['notes']) ? trim($data['notes']) : null,
        );
    }
}
