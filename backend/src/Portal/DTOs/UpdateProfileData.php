<?php

declare(strict_types=1);

namespace SkyFi\Portal\DTOs;

final class UpdateProfileData
{
    public function __construct(
        public readonly ?string $fullName,
        public readonly ?string $phone,
        public readonly ?string $whatsapp,
        public readonly ?string $email,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $area,
        public readonly ?string $emergencyContactName,
        public readonly ?string $emergencyContactPhone,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['full_name']) ? (string) $data['full_name'] : null,
            isset($data['phone']) ? (string) $data['phone'] : null,
            isset($data['whatsapp']) ? (string) $data['whatsapp'] : null,
            isset($data['email']) ? (string) $data['email'] : null,
            isset($data['address']) ? (string) $data['address'] : null,
            isset($data['city']) ? (string) $data['city'] : null,
            isset($data['area']) ? (string) $data['area'] : null,
            isset($data['emergency_contact_name']) ? (string) $data['emergency_contact_name'] : null,
            isset($data['emergency_contact_phone']) ? (string) $data['emergency_contact_phone'] : null,
        );
    }
}
