<?php

declare(strict_types=1);

namespace SkyFi\Customers\Models;

final class Customer
{
    /**
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly string $customerCode,
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
        public readonly string $status,
        public readonly ?string $registrationDate,
        public readonly ?string $installationDate,
        public readonly ?int $assignedPackageId,
        public readonly ?string $connectionStatus,
        public readonly ?int $installationTechnicianId,
        public readonly ?string $emergencyContactName,
        public readonly ?string $emergencyContactPhone,
        public readonly int $createdBy,
        public readonly ?int $updatedBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $deletedAt,
        public readonly ?array $raw = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customerCode,
            'full_name' => $this->fullName,
            'father_husband_name' => $this->fatherHusbandName,
            'cnic' => $this->cnic,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'area' => $this->area,
            'notes' => $this->notes,
            'status' => $this->status,
            'registration_date' => $this->registrationDate,
            'installation_date' => $this->installationDate,
            'assigned_package_id' => $this->assignedPackageId,
            'connection_status' => $this->connectionStatus,
            'installation_technician_id' => $this->installationTechnicianId,
            'emergency_contact_name' => $this->emergencyContactName,
            'emergency_contact_phone' => $this->emergencyContactPhone,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }

    /** Hydrate from a database row. */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            customerCode: (string) $row['customer_code'],
            fullName: (string) $row['full_name'],
            fatherHusbandName: $row['father_husband_name'] ?? null,
            cnic: $row['cnic'] ?? null,
            phone: (string) $row['phone'],
            whatsapp: $row['whatsapp'] ?? null,
            email: $row['email'] ?? null,
            address: (string) $row['address'],
            city: (string) $row['city'],
            area: (string) $row['area'],
            notes: $row['notes'] ?? null,
            status: (string) $row['status'],
            registrationDate: $row['registration_date'] ?? null,
            installationDate: $row['installation_date'] ?? null,
            assignedPackageId: isset($row['assigned_package_id']) ? (int) $row['assigned_package_id'] : null,
            connectionStatus: $row['connection_status'] ?? null,
            installationTechnicianId: isset($row['installation_technician_id']) ? (int) $row['installation_technician_id'] : null,
            emergencyContactName: $row['emergency_contact_name'] ?? null,
            emergencyContactPhone: $row['emergency_contact_phone'] ?? null,
            createdBy: (int) $row['created_by'],
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            deletedAt: $row['deleted_at'] ?? null,
            raw: $row,
        );
    }
}
