<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Models;

/**
 * @param array<string, mixed>|null $raw
 */
final class Tower
{
    public function __construct(
        public readonly int $id,
        public readonly int $popSiteId,
        public readonly ?string $popSiteName,
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
        return [
            'id' => $this->id,
            'pop_site_id' => $this->popSiteId,
            'pop_site_name' => $this->popSiteName,
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
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }

    /** Hydrate from a database row. */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            popSiteId: (int) $row['pop_site_id'],
            popSiteName: $row['pop_site_name'] ?? null,
            name: (string) $row['name'],
            code: $row['code'] ?? null,
            towerType: (string) $row['tower_type'],
            heightMeters: $row['height_meters'] ?? null,
            owner: (string) $row['owner'],
            addressLine1: $row['address_line1'] ?? null,
            city: $row['city'] ?? null,
            region: $row['region'] ?? null,
            gpsLatitude: $row['gps_latitude'] ?? null,
            gpsLongitude: $row['gps_longitude'] ?? null,
            status: (string) $row['status'],
            notes: $row['notes'] ?? null,
            createdBy: (int) $row['created_by'],
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            deletedAt: $row['deleted_at'] ?? null,
            raw: $row,
        );
    }
}