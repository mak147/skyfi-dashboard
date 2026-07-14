<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Contracts;

interface MonitoringDashboardServiceContract
{
    /** @return array<string, mixed> */
    public function getOverview(): array;

    /** @return array<string, mixed> */
    public function getDeviceHealthList(int $page = 1, int $perPage = 20, ?string $deviceType = null, ?string $status = null): array;

    /** @return array<string, mixed> */
    public function getRouterDetailedMetrics(int $routerId): array;
}
