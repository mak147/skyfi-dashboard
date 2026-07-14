<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Models;

/**
 * @param array<string, mixed>|null $raw
 */
final class NetworkDevice
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $popSiteId,
        public readonly ?string $popSiteName,
        public readonly ?int $towerId,
        public readonly ?string $towerName,
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
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?int $mikrotikRouterId,
        public readonly ?string $mikrotikRouterName,
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
        $data = [
            'id' => $this->id,
            'pop_site_id' => $this->popSiteId,
            'pop_site_name' => $this->popSiteName,
            'tower_id' => $this->towerId,
            'tower_name' => $this->towerName,
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
            'status' => $this->status,
            'notes' => $this->notes,
            'mikrotik_router_id' => $this->mikrotikRouterId,
            'mikrotik_router_name' => $this->mikrotikRouterName,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

        // Never expose encrypted password
        return $data;
    }

    /** Hydrate from a database row. */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            popSiteId: isset($row['pop_site_id']) ? (int) $row['pop_site_id'] : null,
            popSiteName: $row['pop_site_name'] ?? null,
            towerId: isset($row['tower_id']) ? (int) $row['tower_id'] : null,
            towerName: $row['tower_name'] ?? null,
            name: (string) $row['name'],
            deviceType: (string) $row['device_type'],
            vendor: $row['vendor'] ?? null,
            model: $row['model'] ?? null,
            serialNumber: $row['serial_number'] ?? null,
            macAddress: $row['mac_address'] ?? null,
            ipAddress: $row['ip_address'] ?? null,
            firmwareVersion: $row['firmware_version'] ?? null,
            locationDescription: $row['location_description'] ?? null,
            managementVlan: isset($row['management_vlan']) ? (int) $row['management_vlan'] : null,
            managementUsername: $row['management_username'] ?? null,
            status: (string) $row['status'],
            notes: $row['notes'] ?? null,
            mikrotikRouterId: isset($row['mikrotik_router_id']) ? (int) $row['mikrotik_router_id'] : null,
            mikrotikRouterName: $row['mikrotik_router_name'] ?? null,
            createdBy: (int) $row['created_by'],
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            deletedAt: $row['deleted_at'] ?? null,
            raw: $row,
        );
    }
}