<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

use SkyFi\Monitoring\DomainModels\DeviceStatusHistory;

interface DeviceStatusRepositoryContract
{
    public function recordStatus(
        string $deviceType,
        int $deviceId,
        string $status,
        ?float $latencyMs = null,
        ?string $errorMessage = null,
    ): DeviceStatusHistory;

    /** @return array<int, DeviceStatusHistory> */
    public function getHistoryForDevice(string $deviceType, int $deviceId, int $limit = 50): array;

    public function getLatestForDevice(string $deviceType, int $deviceId): ?DeviceStatusHistory;
}
