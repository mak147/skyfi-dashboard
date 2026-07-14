<?php

declare(strict_types=1);

namespace SkyFi\Customers\Data;

use SkyFi\Shared\Exceptions\ValidationException;

final class UpdateCustomerData
{
    public function __construct(
        public readonly string $fullName,
        public readonly ?string $fatherHusbandName,
        public readonly ?string $cnic,
        public readonly string $phone,
        public readonly ?string $whatsapp,
        public readonly ?string $email,
        public readonly string $address,
        public readonly string $city,
        public readonly string $area,
        public readonly ?string $notes,
        public readonly ?string $registrationDate,
        public readonly ?string $installationDate,
        public readonly ?int $installationTechnicianId,
        public readonly ?string $emergencyContactName,
        public readonly ?string $emergencyContactPhone,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $errors = [];

        $fullName = trim((string) ($data['full_name'] ?? ''));
        if ($fullName === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Full name is required.', 'source' => ['pointer' => '/data/attributes/full_name']];
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Phone number is required.', 'source' => ['pointer' => '/data/attributes/phone']];
        }

        $address = trim((string) ($data['address'] ?? ''));
        if ($address === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Address is required.', 'source' => ['pointer' => '/data/attributes/address']];
        }

        $city = trim((string) ($data['city'] ?? ''));
        if ($city === '') {
            $errors[] = ['code' => 'required', 'detail' => 'City is required.', 'source' => ['pointer' => '/data/attributes/city']];
        }

        $area = trim((string) ($data['area'] ?? ''));
        if ($area === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Area is required.', 'source' => ['pointer' => '/data/attributes/area']];
        }

        $email = isset($data['email']) && $data['email'] !== '' ? trim((string) $data['email']) : null;
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['code' => 'email', 'detail' => 'Please enter a valid email address.', 'source' => ['pointer' => '/data/attributes/email']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self(
            fullName: $fullName,
            fatherHusbandName: isset($data['father_husband_name']) && $data['father_husband_name'] !== '' ? trim((string) $data['father_husband_name']) : null,
            cnic: isset($data['cnic']) && $data['cnic'] !== '' ? trim((string) $data['cnic']) : null,
            phone: $phone,
            whatsapp: isset($data['whatsapp']) && $data['whatsapp'] !== '' ? trim((string) $data['whatsapp']) : null,
            email: $email,
            address: $address,
            city: $city,
            area: $area,
            notes: isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
            registrationDate: isset($data['registration_date']) && $data['registration_date'] !== '' ? (string) $data['registration_date'] : null,
            installationDate: isset($data['installation_date']) && $data['installation_date'] !== '' ? (string) $data['installation_date'] : null,
            installationTechnicianId: isset($data['installation_technician_id']) && is_numeric($data['installation_technician_id']) ? (int) $data['installation_technician_id'] : null,
            emergencyContactName: isset($data['emergency_contact_name']) && $data['emergency_contact_name'] !== '' ? trim((string) $data['emergency_contact_name']) : null,
            emergencyContactPhone: isset($data['emergency_contact_phone']) && $data['emergency_contact_phone'] !== '' ? trim((string) $data['emergency_contact_phone']) : null,
        );
    }
}
