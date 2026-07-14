<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class CreateNetworkDeviceData
{
    public function __construct(
        public readonly ?int $popSiteId,
        public readonly ?int $towerId,
        public readonly string $name,
        public readonly string $deviceType,
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
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?int $mikrotikRouterId,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'pop_site_id' => $this->popSiteId,
            'tower_id' => $this->towerId,
            'name' => $this->name,
            'device_type' => $this->deviceType,
            'vendor' => $this->vendor,
            'model' => $this->model,
            'serial_number' => $this->serialNumber,
            'mac_address' => $this->macAddress,
            'ip_address' => $this->ipAddress,
            'firmware_version' => $this->firmwareVersion,
            'location_description' => $this->locationDescription,
            'management_vlan' => $this->managementVlan,
            'management_username' => $this->managementUsername,
            'management_password' => $this->managementPassword, // Will be encrypted by service
            'status' => $this->status,
            'notes' => $this->notes,
            'mikrotik_router_id' => $this->mikrotikRouterId,
        ];
    }
}