<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Services;

use SkyFi\Infrastructure\Contracts\NetworkDeviceServiceContract;
use SkyFi\Infrastructure\Data\NetworkDeviceListFilters;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterHealthServiceContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Monitoring\Contracts\AlertManagementServiceContract;
use SkyFi\Monitoring\Contracts\DeviceHealthPollingServiceContract;
use SkyFi\Monitoring\Contracts\DeviceStatusRepositoryContract;
use SkyFi\Monitoring\Contracts\EventLoggingServiceContract;
use SkyFi\Monitoring\Contracts\InterfaceSnapshotRepositoryContract;
use SkyFi\Monitoring\DTOs\CreateAlertData;
use SkyFi\Monitoring\DTOs\LogMonitoringEventData;
use SkyFi\Monitoring\DTOs\SyncEventLogData;
use SkyFi\Rbac\Contracts\AuditLoggerContract;

final class DeviceHealthPollingService implements DeviceHealthPollingServiceContract
{
    public function __construct(
        private readonly RouterServiceContract $routerService,
        private readonly RouterHealthServiceContract $routerHealthService,
        private readonly MikrotikConnectionPoolContract $connectionPool,
        private readonly NetworkDeviceServiceContract $networkDeviceService,
        private readonly DeviceStatusRepositoryContract $statusRepo,
        private readonly InterfaceSnapshotRepositoryContract $ifaceRepo,
        private readonly AlertManagementServiceContract $alertService,
        private readonly EventLoggingServiceContract $eventService,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    /** @return array<string, mixed> */
    public function pollRouterHealth(int $routerId, ?int $actorId = null, ?string $ip = null, ?string $userAgent = null): array
    {
        $startedAt = microtime(true);
        $router = $this->routerService->get($routerId);
        $routerName = $router->toArray()['name'] ?? "Router #{$routerId}";

        try {
            $snapshot = $this->routerHealthService->check($routerId);
            $latencyMs = $snapshot->latencyMs ?? round((microtime(true) - $startedAt) * 1000, 3);

            if ($snapshot->status === 'online') {
                $this->statusRepo->recordStatus('mikrotik_router', $routerId, 'online', $latencyMs, null);

                // Poll interfaces if online
                $interfaceMetrics = $this->pollRouterInterfaces($routerId);

                // Evaluate thresholds
                if ($snapshot->cpuUsagePercent !== null && $snapshot->cpuUsagePercent > 90.0) {
                    $this->triggerAlert('high_cpu', 'warning', 'mikrotik_router', $routerId, "High CPU on {$routerName}", "CPU utilization is at {$snapshot->cpuUsagePercent}%", "{$snapshot->cpuUsagePercent}%", '90%');
                }

                if ($snapshot->memoryTotalBytes !== null && $snapshot->memoryTotalBytes > 0 && $snapshot->memoryFreeBytes !== null) {
                    $usedMemory = $snapshot->memoryTotalBytes - $snapshot->memoryFreeBytes;
                    $memoryUsagePercent = round(($usedMemory / $snapshot->memoryTotalBytes) * 100, 2);
                    if ($memoryUsagePercent > 90.0) {
                        $this->triggerAlert('high_memory', 'warning', 'mikrotik_router', $routerId, "High Memory on {$routerName}", "Memory utilization is at {$memoryUsagePercent}%", "{$memoryUsagePercent}%", '90%');
                    }
                }

                if ($actorId !== null) {
                    $this->auditLogger->log($actorId, 'poll_router_health', 'mikrotik_router', $routerId, ['status' => 'online', 'latency_ms' => $latencyMs], null, $ip, $userAgent);
                }

                return [
                    'router_id' => $routerId,
                    'status' => 'online',
                    'latency_ms' => $latencyMs,
                    'health_snapshot' => $snapshot->toArray(),
                    'interfaces' => $interfaceMetrics,
                ];
            } else {
                $errorMessage = $snapshot->errorMessage ?? 'Router reported offline status.';
                $this->statusRepo->recordStatus('mikrotik_router', $routerId, 'offline', $latencyMs, $errorMessage);
                $this->triggerAlert('device_offline', 'critical', 'mikrotik_router', $routerId, "Router {$routerName} Offline", $errorMessage, 'offline', 'online');

                if ($actorId !== null) {
                    $this->auditLogger->log($actorId, 'poll_router_health', 'mikrotik_router', $routerId, ['status' => 'offline', 'error' => $errorMessage], null, $ip, $userAgent);
                }

                return [
                    'router_id' => $routerId,
                    'status' => 'offline',
                    'latency_ms' => $latencyMs,
                    'error_message' => $errorMessage,
                    'health_snapshot' => $snapshot->toArray(),
                    'interfaces' => [],
                ];
            }
        } catch (\Throwable $exception) {
            $latencyMs = round((microtime(true) - $startedAt) * 1000, 3);
            $errorMessage = substr($exception->getMessage(), 0, 500);
            $this->statusRepo->recordStatus('mikrotik_router', $routerId, 'offline', $latencyMs, $errorMessage);
            $this->triggerAlert('device_offline', 'critical', 'mikrotik_router', $routerId, "Router {$routerName} Unreachable", $errorMessage, 'offline', 'online');

            return [
                'router_id' => $routerId,
                'status' => 'offline',
                'latency_ms' => $latencyMs,
                'error_message' => $errorMessage,
                'interfaces' => [],
            ];
        }
    }

    /** @return array<string, mixed> */
    public function pollNetworkDeviceHealth(int $deviceId, ?int $actorId = null, ?string $ip = null, ?string $userAgent = null): array
    {
        $startedAt = microtime(true);
        $device = $this->networkDeviceService->get($deviceId);
        $status = $device->status;

        // If device has an associated MikroTik Router or IP, simulate reachability ping/status check
        $latencyMs = round((microtime(true) - $startedAt) * 1000, 3);
        $isOnline = in_array($status, ['deployed', 'inventory'], true);
        $recordedStatus = $isOnline ? 'online' : ($status === 'maintenance' ? 'maintenance' : 'offline');

        $this->statusRepo->recordStatus('network_device', $deviceId, $recordedStatus, $latencyMs, null);

        if ($recordedStatus === 'offline') {
            $this->triggerAlert('device_offline', 'critical', 'network_device', $deviceId, "Network Device {$device->name} Offline", "Device status reported as {$status}", $status, 'online');
        }

        if ($actorId !== null) {
            $this->auditLogger->log($actorId, 'poll_network_device_health', 'network_device', $deviceId, ['status' => $recordedStatus], null, $ip, $userAgent);
        }

        return [
            'device_id' => $deviceId,
            'status' => $recordedStatus,
            'latency_ms' => $latencyMs,
            'device_type' => $device->deviceType,
            'ip_address' => $device->ipAddress,
        ];
    }

    /** @return array{routers_polled: int, devices_polled: int, errors: int} */
    public function pollAllDevices(?int $actorId = null, ?string $ip = null, ?string $userAgent = null): array
    {
        $startedAt = microtime(true);
        $routersPolled = 0;
        $devicesPolled = 0;
        $errors = 0;

        // Poll routers
        try {
            $routerList = $this->routerService->list(new RouterListFilters(perPage: 200));
            foreach ($routerList['items'] as $router) {
                if (!$router->isEnabled()) {
                    continue;
                }
                try {
                    $this->pollRouterHealth($router->id());
                    $routersPolled++;
                } catch (\Throwable) {
                    $errors++;
                }
            }
        } catch (\Throwable) {
            $errors++;
        }

        // Poll network devices
        try {
            $deviceList = $this->networkDeviceService->list(new NetworkDeviceListFilters(perPage: 300));
            foreach ($deviceList['items'] as $device) {
                try {
                    $this->pollNetworkDeviceHealth($device->id);
                    $devicesPolled++;
                } catch (\Throwable) {
                    $errors++;
                }
            }
        } catch (\Throwable) {
            $errors++;
        }

        $elapsedMs = round((microtime(true) - $startedAt) * 1000, 2);
        $this->eventService->recordSyncEvent(new SyncEventLogData(
            routerId: null,
            syncType: 'full_topology_sync',
            status: $errors === 0 ? 'success' : ($routersPolled + $devicesPolled > 0 ? 'partial' : 'failed'),
            itemsSynced: $routersPolled + $devicesPolled,
            errorMessage: $errors > 0 ? "Encountered {$errors} errors during polling." : null,
            executionTimeMs: $elapsedMs
        ));

        if ($actorId !== null) {
            $this->auditLogger->log($actorId, 'poll_all_devices', 'monitoring_system', null, ['routers' => $routersPolled, 'devices' => $devicesPolled, 'errors' => $errors], null, $ip, $userAgent);
        }

        return [
            'routers_polled' => $routersPolled,
            'devices_polled' => $devicesPolled,
            'errors' => $errors,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function pollRouterInterfaces(int $routerId): array
    {
        try {
            $connection = $this->routerService->connectionData($routerId);
            $responses = $this->connectionPool->executeBatch($connection, [
                ['/interface/print']
            ]);
            $ifaces = $responses[0] ?? [];
            $now = gmdate('Y-m-d H:i:s');
            $results = [];

            foreach ($ifaces as $iface) {
                $name = $iface['name'] ?? null;
                if ($name === null) {
                    continue;
                }
                $type = $iface['type'] ?? null;
                $running = ($iface['running'] ?? 'false') === 'true';
                $disabled = ($iface['disabled'] ?? 'false') === 'true';
                $mtu = isset($iface['mtu']) && preg_match('/^\d+$/', $iface['mtu']) ? (int) $iface['mtu'] : null;
                $rxBytes = isset($iface['rx-byte']) && preg_match('/^\d+$/', $iface['rx-byte']) ? (int) $iface['rx-byte'] : 0;
                $txBytes = isset($iface['tx-byte']) && preg_match('/^\d+$/', $iface['tx-byte']) ? (int) $iface['tx-byte'] : 0;

                $linkStatus = ($running && !$disabled) ? 'up' : 'down';

                // Calculate bps
                $previous = $this->ifaceRepo->getLatestSnapshotForInterface($routerId, $name);
                $rxBps = 0;
                $txBps = 0;

                if ($previous !== null && $previous->rxBytes <= $rxBytes && $previous->txBytes <= $txBytes) {
                    $deltaRx = $rxBytes - $previous->rxBytes;
                    $deltaTx = $txBytes - $previous->txBytes;
                    $prevTime = strtotime($previous->checkedAt);
                    $currTime = strtotime($now);
                    $deltaT = max(1, $currTime - $prevTime);
                    $rxBps = (int) round(($deltaRx * 8) / $deltaT);
                    $txBps = (int) round(($deltaTx * 8) / $deltaT);
                }

                $snapshot = $this->ifaceRepo->recordSnapshot([
                    'router_id' => $routerId,
                    'interface_name' => $name,
                    'interface_type' => $type,
                    'running' => $running,
                    'disabled' => $disabled,
                    'mtu' => $mtu,
                    'rx_bytes' => $rxBytes,
                    'tx_bytes' => $txBytes,
                    'rx_bps' => $rxBps,
                    'tx_bps' => $txBps,
                    'link_status' => $linkStatus,
                    'checked_at' => $now,
                ]);

                if ($linkStatus === 'down' && !in_array(strtolower((string) $type), ['loopback', 'dummy'], true)) {
                    // Log interface down event if previously up
                    if ($previous !== null && $previous->linkStatus === 'up') {
                        $this->triggerAlert(
                            'interface_down',
                            'warning',
                            'mikrotik_router',
                            $routerId,
                            "Interface {$name} Down on Router #{$routerId}",
                            "Interface link status transitioned from up to down.",
                            'down',
                            'up'
                        );
                    }
                }

                $results[] = $snapshot->toArray();
            }

            return $results;
        } catch (MikrotikConnectionException|MikrotikCommandException|\Throwable) {
            return [];
        }
    }

    private function triggerAlert(string $type, string $severity, string $deviceType, int $deviceId, string $title, string $description, ?string $metricValue, ?string $thresholdValue): void
    {
        try {
            $this->alertService->createAlert(new CreateAlertData(
                alertType: $type,
                severity: $severity,
                deviceType: $deviceType,
                deviceId: $deviceId,
                title: $title,
                description: $description,
                metricValue: $metricValue,
                thresholdValue: $thresholdValue,
                metadata: ['triggered_by' => 'DeviceHealthPollingService']
            ));
        } catch (\Throwable) {
            // Prevent alert creation failures from halting health polling loop
        }
    }
}
