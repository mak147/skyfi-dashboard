<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Services;

use SkyFi\Hotspot\Services\HotspotSessionMonitorService;
use SkyFi\Infrastructure\Contracts\NetworkDeviceServiceContract;
use SkyFi\Infrastructure\Data\NetworkDeviceListFilters;
use SkyFi\Mikrotik\Contracts\RouterHealthServiceContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Monitoring\Contracts\AlertRepositoryContract;
use SkyFi\Monitoring\Contracts\DeviceStatusRepositoryContract;
use SkyFi\Monitoring\Contracts\InterfaceSnapshotRepositoryContract;
use SkyFi\Monitoring\Contracts\MonitoringDashboardServiceContract;
use SkyFi\Pppoe\Services\PppoeSessionMonitorService;
use SkyFi\Shared\Exceptions\NotFoundException;

final class MonitoringDashboardService implements MonitoringDashboardServiceContract
{
    public function __construct(
        private readonly RouterServiceContract $routerService,
        private readonly RouterHealthServiceContract $routerHealthService,
        private readonly NetworkDeviceServiceContract $networkDeviceService,
        private readonly AlertRepositoryContract $alertRepo,
        private readonly InterfaceSnapshotRepositoryContract $ifaceRepo,
        private readonly DeviceStatusRepositoryContract $statusRepo,
        private readonly PppoeSessionMonitorService $pppoeMonitor,
        private readonly HotspotSessionMonitorService $hotspotMonitor,
    ) {
    }

    /** @return array<string, mixed> */
    public function getOverview(): array
    {
        // 1. Router counts & online ratio
        $routersOnline = 0;
        $routersOffline = 0;
        $routersTotal = 0;
        $routerList = $this->routerService->list(new RouterListFilters(perPage: 200));

        foreach ($routerList['items'] as $router) {
            $routersTotal++;
            $status = $router->toArray()['last_connection_status'] ?? 'unknown';
            if ($status === 'online') {
                $routersOnline++;
            } elseif ($status === 'offline') {
                $routersOffline++;
            }
        }

        // 2. Network Devices summary
        $devicesOnline = 0;
        $devicesOffline = 0;
        $devicesTotal = 0;
        $deviceList = $this->networkDeviceService->list(new NetworkDeviceListFilters(perPage: 300));
        foreach ($deviceList['items'] as $device) {
            $devicesTotal++;
            if (in_array($device->status, ['deployed', 'inventory'], true)) {
                $devicesOnline++;
            } elseif ($device->status === 'offline') {
                $devicesOffline++;
            }
        }

        // 3. Active sessions across all routers
        $pppoeSessionsCount = 0;
        try {
            $pppoeSessionsCount = count($this->pppoeMonitor->listActiveSessions());
        } catch (\Throwable) {
            // Non-blocking
        }

        $hotspotSessionsCount = 0;
        try {
            $hotspotSessionsCount = count($this->hotspotMonitor->listActiveSessions());
        } catch (\Throwable) {
            // Non-blocking
        }

        // 4. Bandwidth utilization across all interfaces
        $totalRxBps = 0;
        $totalTxBps = 0;
        foreach ($routerList['items'] as $router) {
            if (!$router->isEnabled()) {
                continue;
            }
            $ifaces = $this->ifaceRepo->getLatestSnapshotsForRouter($router->id());
            foreach ($ifaces as $iface) {
                if ($iface->linkStatus === 'up') {
                    $totalRxBps += $iface->rxBps;
                    $totalTxBps += $iface->txBps;
                }
            }
        }

        // 5. Alert counts
        $alertCounts = $this->alertRepo->getAlertCounts();

        return [
            'routers' => [
                'total' => $routersTotal,
                'online' => $routersOnline,
                'offline' => $routersOffline,
            ],
            'network_devices' => [
                'total' => $devicesTotal,
                'online' => $devicesOnline,
                'offline' => $devicesOffline,
            ],
            'sessions' => [
                'active_pppoe' => $pppoeSessionsCount,
                'active_hotspot' => $hotspotSessionsCount,
                'total_active' => $pppoeSessionsCount + $hotspotSessionsCount,
            ],
            'bandwidth' => [
                'total_rx_bps' => $totalRxBps,
                'total_tx_bps' => $totalTxBps,
                'formatted_rx' => $this->formatBitsPerSecond($totalRxBps),
                'formatted_tx' => $this->formatBitsPerSecond($totalTxBps),
            ],
            'alerts' => $alertCounts,
            'timestamp' => gmdate('Y-m-d H:i:s'),
        ];
    }

    /** @return array<string, mixed> */
    public function getDeviceHealthList(int $page = 1, int $perPage = 20, ?string $deviceType = null, ?string $status = null): array
    {
        $items = [];

        // Fetch routers
        if ($deviceType === null || $deviceType === 'mikrotik_router') {
            $routerList = $this->routerService->list(new RouterListFilters(perPage: 200));
            foreach ($routerList['items'] as $r) {
                $rArray = $r->toArray();
                $rStatus = $rArray['last_connection_status'] ?? 'unknown';
                if ($status !== null && $rStatus !== $status) {
                    continue;
                }
                $health = $this->routerHealthService->latest($r->id());
                $items[] = [
                    'device_type' => 'mikrotik_router',
                    'id' => $r->id(),
                    'name' => $rArray['name'],
                    'host' => $rArray['host'],
                    'site' => $rArray['site'] ?? null,
                    'status' => $rStatus,
                    'latency_ms' => $health?->latencyMs,
                    'cpu_usage_percent' => $health?->cpuUsagePercent,
                    'memory_usage_percent' => ($health?->memoryTotalBytes > 0 && $health?->memoryFreeBytes !== null)
                        ? round((($health->memoryTotalBytes - $health->memoryFreeBytes) / $health->memoryTotalBytes) * 100, 1)
                        : null,
                    'disk_usage_percent' => ($health?->diskTotalBytes > 0 && $health?->diskFreeBytes !== null)
                        ? round((($health->diskTotalBytes - $health->diskFreeBytes) / $health->diskTotalBytes) * 100, 1)
                        : null,
                    'temperature_celsius' => $health?->temperatureCelsius,
                    'uptime' => $health?->uptime ?? $rArray['last_connected_at'] ?? null,
                    'last_seen' => $rArray['last_connected_at'] ?? null,
                ];
            }
        }

        // Fetch network devices
        if ($deviceType === null || $deviceType === 'network_device') {
            $deviceList = $this->networkDeviceService->list(new NetworkDeviceListFilters(perPage: 300, status: $status));
            foreach ($deviceList['items'] as $d) {
                $latestHistory = $this->statusRepo->getLatestForDevice('network_device', $d->id);
                $dStatus = $d->status === 'deployed' || $d->status === 'inventory' ? 'online' : ($d->status === 'offline' ? 'offline' : 'degraded');
                if ($status !== null && $dStatus !== $status) {
                    continue;
                }
                $items[] = [
                    'device_type' => 'network_device',
                    'id' => $d->id,
                    'name' => $d->name,
                    'host' => $d->ipAddress ?? $d->macAddress ?? 'N/A',
                    'site' => $d->popSiteName ?? $d->towerName ?? null,
                    'status' => $dStatus,
                    'latency_ms' => $latestHistory?->latencyMs,
                    'cpu_usage_percent' => null,
                    'memory_usage_percent' => null,
                    'disk_usage_percent' => null,
                    'temperature_celsius' => null,
                    'uptime' => $latestHistory?->checkedAt ?? null,
                    'last_seen' => $latestHistory?->checkedAt ?? $d->updatedAt,
                ];
            }
        }

        $total = count($items);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($items, $offset, $perPage);

        return [
            'items' => array_values($paginatedItems),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed> */
    public function getRouterDetailedMetrics(int $routerId): array
    {
        $router = $this->routerService->get($routerId);
        $health = $this->routerHealthService->latest($routerId);
        $ifaces = $this->ifaceRepo->getLatestSnapshotsForRouter($routerId);
        $history = $this->statusRepo->getHistoryForDevice('mikrotik_router', $routerId, 25);

        return [
            'router' => $router->toArray(),
            'health_snapshot' => $health?->toArray(),
            'interfaces' => array_map(static fn ($i) => $i->toArray(), $ifaces),
            'status_history' => array_map(static fn ($h) => $h->toArray(), $history),
        ];
    }

    private function formatBitsPerSecond(int $bps): string
    {
        if ($bps >= 1000000000) {
            return round($bps / 1000000000, 2) . ' Gbps';
        }
        if ($bps >= 1000000) {
            return round($bps / 1000000, 2) . ' Mbps';
        }
        if ($bps >= 1000) {
            return round($bps / 1000, 2) . ' Kbps';
        }

        return $bps . ' bps';
    }
}
