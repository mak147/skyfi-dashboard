<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class CreateSectorData
{
    public function __construct(
        public readonly int $towerId,
        public readonly string $name,
        public readonly int $azimuth,
        public readonly ?int $beamwidth,
        public readonly int $frequencyMhz,
        public readonly ?int $channelWidthMhz,
        public readonly ?string $ssid,
        public readonly ?int $eirpDbm,
        public readonly ?int $deviceId,
        public readonly ?int $capacityMbps,
        public readonly ?int $maxSubscribers,
        public readonly string $status,
        public readonly ?string $notes,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tower_id' => $this->towerId,
            'name' => $this->name,
            'azimuth' => $this->azimuth,
            'beamwidth' => $this->beamwidth,
            'frequency_mhz' => $this->frequencyMhz,
            'channel_width_mhz' => $this->channelWidthMhz,
            'ssid' => $this->ssid,
            'eirp_dbm' => $this->eirpDbm,
            'device_id' => $this->deviceId,
            'capacity_mbps' => $this->capacityMbps,
            'max_subscribers' => $this->maxSubscribers,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}