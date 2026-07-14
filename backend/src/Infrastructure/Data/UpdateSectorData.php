<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class UpdateSectorData
{
    public function __construct(
        public readonly ?int $towerId,
        public readonly ?string $name,
        public readonly ?int $azimuth,
        public readonly ?int $beamwidth,
        public readonly ?int $frequencyMhz,
        public readonly ?int $channelWidthMhz,
        public readonly ?string $ssid,
        public readonly ?int $eirpDbm,
        public readonly ?int $deviceId,
        public readonly ?int $capacityMbps,
        public readonly ?int $maxSubscribers,
        public readonly ?string $status,
        public readonly ?string $notes,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];
        if ($this->towerId !== null) $data['tower_id'] = $this->towerId;
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->azimuth !== null) $data['azimuth'] = $this->azimuth;
        if ($this->beamwidth !== null) $data['beamwidth'] = $this->beamwidth;
        if ($this->frequencyMhz !== null) $data['frequency_mhz'] = $this->frequencyMhz;
        if ($this->channelWidthMhz !== null) $data['channel_width_mhz'] = $this->channelWidthMhz;
        if ($this->ssid !== null) $data['ssid'] = $this->ssid;
        if ($this->eirpDbm !== null) $data['eirp_dbm'] = $this->eirpDbm;
        if ($this->deviceId !== null) $data['device_id'] = $this->deviceId;
        if ($this->capacityMbps !== null) $data['capacity_mbps'] = $this->capacityMbps;
        if ($this->maxSubscribers !== null) $data['max_subscribers'] = $this->maxSubscribers;
        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->notes !== null) $data['notes'] = $this->notes;
        return $data;
    }
}