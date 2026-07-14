<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

interface DeviceHealthPollingServiceContract
{
    /** @return array<string, mixed> */
    public function pollRouterHealth(int $routerId, ?int $actorId = null, ?string $ip = null, ?string $userAgent = null): array;

    /** @return array<string, mixed> */
    public function pollNetworkDeviceHealth(int $deviceId, ?int $actorId = null, ?string $ip = null, ?string $userAgent = null): array;

    /** @return array{routers_polled: int, devices_polled: int, errors: int} */
    public function pollAllDevices(?int $actorId = null, ?string $ip = null, ?string $userAgent = null): array;

    /** @return array<int, array<string, mixed>> */
    public function pollRouterInterfaces(int $routerId): array;
}
