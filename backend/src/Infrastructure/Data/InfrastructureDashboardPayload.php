<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Data;

final class InfrastructureDashboardPayload
{
    public function __construct(
        public readonly int $totalPopSites,
        public readonly int $activePopSites,
        public readonly int $totalTowers,
        public readonly int $activeTowers,
        public readonly int $totalSectors,
        public readonly int $activeSectors,
        public readonly int $totalDevices,
        public readonly int $activeDevices,
        public readonly int $offlineDevices,
        public readonly int $maintenanceDevices,
        public readonly array $capacitySummary, // [sector_id => ['name' => '', 'capacity_mbps' => '', 'connected' => '']]
        public readonly array $statusBreakdown, // ['pop_sites' => [], 'towers' => [], 'sectors' => [], 'devices' => []]
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total_pop_sites' => $this->totalPopSites,
            'active_pop_sites' => $this->activePopSites,
            'total_towers' => $this->totalTowers,
            'active_towers' => $this->activeTowers,
            'total_sectors' => $this->totalSectors,
            'active_sectors' => $this->activeSectors,
            'total_devices' => $this->totalDevices,
            'active_devices' => $this->activeDevices,
            'offline_devices' => $this->offlineDevices,
            'maintenance_devices' => $this->maintenanceDevices,
            'capacity_summary' => $this->capacitySummary,
            'status_breakdown' => $this->statusBreakdown,
        ];
    }
}