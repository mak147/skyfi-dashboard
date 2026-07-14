<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Models;

/**
 * @param array<string, mixed>|null $raw
 */
final class Sector
{
    public function __construct(
        public readonly int $id,
        public readonly int $towerId,
        public readonly ?string $towerName,
        public readonly ?string $popSiteName,
        public readonly string $name,
        public readonly int $azimuth,
        public readonly ?int $beamwidth,
        public readonly int $frequencyMhz,
        public readonly ?int $channelWidthMhz,
        public readonly ?string $ssid,
        public readonly ?int $eirpDbm,
        public readonly ?int $deviceId,
        public readonly ?string $deviceName,
        public readonly ?int $capacityMbps,
        public readonly ?int $maxSubscribers,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly int $createdBy,
        public readonly ?int $updatedBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $deletedAt,
        public readonly ?int $connectionCount = null,
        public readonly ?array $raw = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tower_id' => $this->towerId,
            'tower_name' => $this->towerName,
            'pop_site_name' => $this->popSiteName,
            'name' => $this->name,
            'azimuth' => $this->azimuth,
            'beamwidth' => $this->beamwidth,
            'frequency_mhz' => $this->frequencyMhz,
            'channel_width_mhz' => $this->channelWidthMhz,
            'ssid' => $this->ssid,
            'eirp_dbm' => $this->eirpDbm,
            'device_id' => $this->deviceId,
            'device_name' => $this->deviceName,
            'capacity_mbps' => $this->capacityMbps,
            'max_subscribers' => $this->maxSubscribers,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
            'connection_count' => $this->connectionCount,
        ];
    }

    /** Hydrate from a database row. */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            towerId: (int) $row['tower_id'],
            towerName: $row['tower_name'] ?? null,
            popSiteName: $row['pop_site_name'] ?? null,
            name: (string) $row['name'],
            azimuth: (int) $row['azimuth'],
            beamwidth: isset($row['beamwidth']) ? (int) $row['beamwidth'] : null,
            frequencyMhz: (int) $row['frequency_mhz'],
            channelWidthMhz: isset($row['channel_width_mhz']) ? (int) $row['channel_width_mhz'] : null,
            ssid: $row['ssid'] ?? null,
            eirpDbm: isset($row['eirp_dbm']) ? (int) $row['eirp_dbm'] : null,
            deviceId: isset($row['device_id']) ? (int) $row['device_id'] : null,
            deviceName: $row['device_name'] ?? null,
            capacityMbps: isset($row['capacity_mbps']) ? (int) $row['capacity_mbps'] : null,
            maxSubscribers: isset($row['max_subscribers']) ? (int) $row['max_subscribers'] : null,
            status: (string) $row['status'],
            notes: $row['notes'] ?? null,
            createdBy: (int) $row['created_by'],
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            deletedAt: $row['deleted_at'] ?? null,
            connectionCount: isset($row['connection_count']) ? (int) $row['connection_count'] : null,
            raw: $row,
        );
    }
}