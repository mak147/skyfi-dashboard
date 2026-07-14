<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class UpdateTowerData
{
    public function __construct(
        public readonly ?int $popSiteId,
        public readonly ?string $name,
        public readonly ?string $code,
        public readonly ?string $towerType,
        public readonly ?string $heightMeters,
        public readonly ?string $owner,
        public readonly ?string $addressLine1,
        public readonly ?string $city,
        public readonly ?string $region,
        public readonly ?string $gpsLatitude,
        public readonly ?string $gpsLongitude,
        public readonly ?string $status,
        public readonly ?string $notes,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];
        if ($this->popSiteId !== null) $data['pop_site_id'] = $this->popSiteId;
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->code !== null) $data['code'] = $this->code;
        if ($this->towerType !== null) $data['tower_type'] = $this->towerType;
        if ($this->heightMeters !== null) $data['height_meters'] = $this->heightMeters;
        if ($this->owner !== null) $data['owner'] = $this->owner;
        if ($this->addressLine1 !== null) $data['address_line1'] = $this->addressLine1;
        if ($this->city !== null) $data['city'] = $this->city;
        if ($this->region !== null) $data['region'] = $this->region;
        if ($this->gpsLatitude !== null) $data['gps_latitude'] = $this->gpsLatitude;
        if ($this->gpsLongitude !== null) $data['gps_longitude'] = $this->gpsLongitude;
        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->notes !== null) $data['notes'] = $this->notes;
        return $data;
    }
}