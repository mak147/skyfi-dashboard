<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\NetworkDeviceListFilters;
use SkyFi\Infrastructure\Models\NetworkDevice;

interface NetworkDeviceRepositoryContract
{
    /** Find a network device by ID, or null if not found (including soft-deleted). */
    public function find(int $id): ?NetworkDevice;

    /** Find a network device by ID that is not soft-deleted. */
    public function findActive(int $id): ?NetworkDevice;

    /** Check if a serial number already exists. */
    public function serialExists(string $serial, ?int $excludeId = null): bool;

    /** Check if a MAC address already exists. */
    public function macExists(string $mac, ?int $excludeId = null): bool;

    /** Check if an IP address already exists. */
    public function ipExists(string $ip, ?int $excludeId = null): bool;

    /**
     * List network devices with filtering, sorting, and pagination.
     *
     * @return array{items: array<NetworkDevice>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function list(NetworkDeviceListFilters $filters): array;

    /** Create a new network device and return the created model. */
    public function create(array $data): NetworkDevice;

    /** Update a network device and return the updated model. */
    public function update(int $id, array $data): NetworkDevice;

    /** Soft-delete a network device. */
    public function softDelete(int $id): void;

    /** Update only the status field. */
    public function updateStatus(int $id, string $status): void;

    /** Get devices by POP site. */
    public function getByPopSite(int $popSiteId): array;

    /** Get devices by tower. */
    public function getByTower(int $towerId): array;

    /** Get devices by type. */
    public function getByType(string $type): array;

    /** Get device by MikroTik router ID. */
    public function getByMikrotikRouterId(int $mikrotikRouterId): ?NetworkDevice;
}