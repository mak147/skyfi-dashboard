<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class UpdatePopSiteData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $code,
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
        public readonly ?string $powerStatus,
        public readonly ?string $fiberProvider,
        public readonly ?string $status,
        public readonly ?string $notes,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->code !== null) $data['code'] = $this->code;
        if ($this->addressLine1 !== null) $data['address_line1'] = $this->addressLine1;
        if ($this->addressLine2 !== null) $data['address_line2'] = $this->addressLine2;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->region !== null) $data['region'] = $this->region;
        if ($this->country !== null) $data['country'] = $this->country;
        if ($this->gpsLatitude !== null) $data['gps_latitude'] = $this->gpsLatitude;
        if ($this->gpsLongitude !== null) $data['gps_longitude'] = $this->gpsLongitude;
        if ($this->contactPerson !== null) $data['contact_person'] = $this->contactPerson;
        if ($this->contactPhone !== null) $data['contact_phone'] = $this->contactPhone;
        if ($this->contactEmail !== null) $data['contact_email'] = $this->contactEmail;
        if ($this->powerStatus !== null) $data['power_status'] = $this->powerStatus;
        if ($this->fiberProvider !== null) $data['fiber_provider'] = $this->fiberProvider;
        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->notes !== null) $data['notes'] = $this->notes;
        return $data;
    }
}