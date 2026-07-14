<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\CreateNetworkDeviceData;
use SkyFi\Infrastructure\Data\NetworkDeviceListFilters;
use SkyFi\Infrastructure\Data\UpdateNetworkDeviceData;
use SkyFi\Infrastructure\Models\NetworkDevice;

interface NetworkDeviceServiceContract
{
    /** List network devices with filtering, sorting, and pagination. */
    public function list(NetworkDeviceListFilters $filters): array;

    /** Get a network device by ID. */
    public function get(int $id): NetworkDevice;

    /** Create a new network device. */
    public function create(CreateNetworkDeviceData $data, int $authUserId, ?string $ip, ?string $ua): NetworkDevice;

    /** Update a network device. */
    public function update(int $id, UpdateNetworkDeviceData $data, int $authUserId, ?string $ip, ?string $ua): NetworkDevice;

    /** Soft delete a network device. */
    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void;

    /** Change device status. */
    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): NetworkDevice;

    /** Get devices by POP site. */
    public function getByPopSite(int $popSiteId): array;

    /** Get devices by tower. */
    public function getByTower(int $towerId): array;

    /** Get devices by type. */
    public function getByType(string $type): array;
}