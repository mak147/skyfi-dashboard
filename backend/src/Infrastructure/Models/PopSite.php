<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Models;

/**
 * @param array<string, mixed>|null $raw
 */
final class PopSite
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $city,
        public readonly ?string $region,
        public readonly ?string $country,
        public readonly ?string $gpsLatitude,
        public readonly ?string $gpsLongitude,
        public readonly ?string $contactPerson,
        public readonly ?string $contactPhone,
        public readonly ?string $contactEmail,
        public readonly string $powerStatus,
        public readonly ?string $fiberProvider,
        public readonly string $status,
        public readonly ?string $notes,
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
            'name' => $this->name,
            'code' => $this->code,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'gps_latitude' => $this->gpsLatitude,
            'gps_longitude' => $this->gpsLongitude,
            'contact_person' => $this->contactPerson,
            'contact_phone' => $this->contactPhone,
            'contact_email' => $this->contactEmail,
            'power_status' => $this->powerStatus,
            'fiber_provider' => $this->fiberProvider,
            'status' => $this->status,
            'notes' => $this->notes,
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
            name: (string) $row['name'],
            code: (string) $row['code'],
            addressLine1: $row['address_line1'] ?? null,
            addressLine2: $row['address_line2'] ?? null,
            city: $row['city'] ?? null,
            region: $row['region'] ?? null,
            country: $row['country'] ?? 'Pakistan',
            gpsLatitude: $row['gps_latitude'] ?? null,
            gpsLongitude: $row['gps_longitude'] ?? null,
            contactPerson: $row['contact_person'] ?? null,
            contactPhone: $row['contact_phone'] ?? null,
            contactEmail: $row['contact_email'] ?? null,
            powerStatus: (string) $row['power_status'],
            fiberProvider: $row['fiber_provider'] ?? null,
            status: (string) $row['status'],
            notes: $row['notes'] ?? null,
            createdBy: (int) $row['created_by'],
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            deletedAt: $row['deleted_at'] ?? null,
            raw: $row,
        );
    }
}