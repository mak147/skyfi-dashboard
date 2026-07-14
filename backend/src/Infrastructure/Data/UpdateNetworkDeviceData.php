<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class UpdateNetworkDeviceData
{
    public function __construct(
        public readonly ?int $popSiteId,
        public readonly ?int $towerId,
        public readonly ?string $name,
        public readonly ?string $deviceType,
        public readonly ?string $vendor,
        public readonly ?string $model,
        public readonly ?string $serialNumber,
        public readonly ?string $macAddress,
        public readonly ?string $ipAddress,
        public readonly ?string $firmwareVersion,
        public readonly ?string $locationDescription,
        public readonly ?int $managementVlan,
        public readonly ?string $managementUsername,
        public readonly ?string $managementPassword,
        public readonly ?string $status,
        public readonly ?string $notes,
        public readonly ?int $mikrotikRouterId,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];
        if ($this->popSiteId !== null) $data['pop_site_id'] = $this->popSiteId;
        if ($this->towerId !== null) $data['tower_id'] = $this->towerId;
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->deviceType !== null) $data['device_type'] = $this->deviceType;
        if ($this->vendor !== null) $data['vendor'] = $this->vendor;
        if ($this->model !== null) $data['model'] = $this->model;
        if ($this->serialNumber !== null) $data['serial_number'] = $this->serialNumber;
        if ($this->macAddress !== null) $data['mac_address'] = $this->macAddress;
        if ($this->ipAddress !== null) $data['ip_address'] = $this->ipAddress;
        if ($this->firmwareVersion !== null) $data['firmware_version'] = $this->firmwareVersion;
        if ($this->locationDescription !== null) $data['location_description'] = $this->locationDescription;
        if ($this->managementVlan !== null) $data['management_vlan'] = $this->managementVlan;
        if ($this->managementUsername !== null) $data['management_username'] = $this->managementUsername;
        if ($this->managementPassword !== null) $data['management_password'] = $this->managementPassword;
        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->notes !== null) $data['notes'] = $this->notes;
        if ($this->mikrotikRouterId !== null) $data['mikrotik_router_id'] = $this->mikrotikRouterId;
        return $data;
    }
}