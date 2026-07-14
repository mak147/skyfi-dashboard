<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class CreatePopSiteData
{
    public function __construct(
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
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country ?? 'Pakistan',
            'gps_latitude' => $this->gpsLatitude,
            'gps_longitude' => $this->gpsLongitude,
            'contact_person' => $this->contactPerson,
            'contact_phone' => $this->contactPhone,
            'contact_email' => $this->contactEmail,
            'power_status' => $this->powerStatus,
            'fiber_provider' => $this->fiberProvider,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}