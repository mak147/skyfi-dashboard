<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class CreateTowerData
{
    public function __construct(
        public readonly int $popSiteId,
        public readonly string $name,
        public readonly ?string $code,
        public readonly string $towerType,
        public readonly ?string $heightMeters,
        public readonly string $owner,
        public readonly ?string $addressLine1,
        public readonly ?string $city,
        public readonly ?string $region,
        public readonly ?string $gpsLatitude,
        public readonly ?string $gpsLongitude,
        public readonly string $status,
        public readonly ?string $notes,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'pop_site_id' => $this->popSiteId,
            'name' => $this->name,
            'code' => $this->code,
            'tower_type' => $this->towerType,
            'height_meters' => $this->heightMeters,
            'owner' => $this->owner,
            'address_line1' => $this->addressLine1,
            'city' => $this->city,
            'region' => $this->region,
            'gps_latitude' => $this->gpsLatitude,
            'gps_longitude' => $this->gpsLongitude,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}