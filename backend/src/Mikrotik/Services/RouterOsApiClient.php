<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Services;

use SkyFi\Mikrotik\Contracts\MikrotikClientContract;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\DomainModels\RouterConnectionData;
use SkyFi\Mikrotik\DomainModels\RouterDiscovery;
use SkyFi\Mikrotik\DomainModels\RouterHealthSnapshot;

/** Maps read-only RouterOS API responses to SkyFi's vendor-isolated models. */
final class RouterOsApiClient implements MikrotikClientContract
{
    public function __construct(private readonly MikrotikConnectionPoolContract $pool)
    {
    }

    public function testConnection(RouterConnectionData $connection): array
    {
        $startedAt = microtime(true);
        $responses = $this->pool->executeBatch($connection, [
            ['/system/identity/print'],
            ['/system/resource/print'],
        ]);
        $identity = $responses[0][0] ?? [];
        $resource = $responses[1][0] ?? [];

        return [
            'connected' => true,
            'latency_ms' => round((microtime(true) - $startedAt) * 1000, 3),
            'identity' => $identity['name'] ?? null,
            'routeros_version' => $resource['version'] ?? null,
            'model' => $resource['board-name'] ?? $resource['platform'] ?? null,
        ];
    }

    public function discover(int $routerId, RouterConnectionData $connection): RouterDiscovery
    {
        $startedAt = microtime(true);
        $responses = $this->pool->executeBatch($connection, [
            ['/system/identity/print'],
            ['/system/resource/print'],
            ['/interface/print'],
            ['/ip/address/print'],
            ['/ppp/active/print'],
            ['/ip/hotspot/active/print'],
            ['/queue/simple/print'],
        ]);
        $resource = $responses[1][0] ?? [];

        return new RouterDiscovery(
            identity: ($responses[0][0] ?? [])['name'] ?? null,
            routerosVersion: $resource['version'] ?? null,
            model: $resource['board-name'] ?? $resource['platform'] ?? null,
            uptime: $resource['uptime'] ?? null,
            cpuUsagePercent: $this->decimal($resource['cpu-load'] ?? null),
            memoryTotalBytes: $this->integer($resource['total-memory'] ?? null),
            memoryFreeBytes: $this->integer($resource['free-memory'] ?? null),
            diskTotalBytes: $this->integer($resource['total-hdd-space'] ?? null),
            diskFreeBytes: $this->integer($resource['free-hdd-space'] ?? null),
            interfaces: array_map([$this, 'interfaceAttributes'], $responses[2]),
            ipAddresses: array_map([$this, 'ipAddressAttributes'], $responses[3]),
            activeUsersCount: count($responses[4]) + count($responses[5]),
            queueCount: count($responses[6]),
            latencyMs: round((microtime(true) - $startedAt) * 1000, 3),
            discoveredAt: gmdate('Y-m-d H:i:s'),
        );
    }

    public function checkHealth(int $routerId, RouterConnectionData $connection): RouterHealthSnapshot
    {
        $startedAt = microtime(true);
        $responses = $this->pool->executeBatch($connection, [
            ['/system/resource/print'],
            ['/interface/print'],
            ['/ppp/active/print'],
            ['/ip/hotspot/active/print'],
            ['/queue/simple/print'],
        ]);
        $temperature = null;
        try {
            $health = $this->pool->executeBatch($connection, [['/system/health/print']]);
            $temperature = $this->temperature($health[0][0] ?? []);
        } catch (\Throwable) {
            // Not every RouterOS board exposes /system/health; health remains valid without it.
        }
        $resource = $responses[0][0] ?? [];
        $traffic = $this->trafficSummary($responses[1]);

        return new RouterHealthSnapshot(
            id: null,
            routerId: $routerId,
            status: 'online',
            latencyMs: round((microtime(true) - $startedAt) * 1000, 3),
            cpuUsagePercent: $this->decimal($resource['cpu-load'] ?? null),
            memoryTotalBytes: $this->integer($resource['total-memory'] ?? null),
            memoryFreeBytes: $this->integer($resource['free-memory'] ?? null),
            diskTotalBytes: $this->integer($resource['total-hdd-space'] ?? null),
            diskFreeBytes: $this->integer($resource['free-hdd-space'] ?? null),
            temperatureCelsius: $temperature,
            trafficRxBytes: $traffic['rx'],
            trafficTxBytes: $traffic['tx'],
            activeUsersCount: count($responses[2]) + count($responses[3]),
            queueCount: count($responses[4]),
            uptime: $resource['uptime'] ?? null,
            errorMessage: null,
            checkedAt: gmdate('Y-m-d H:i:s'),
        );
    }

    /** @param array<string, string> $attributes @return array<string, mixed> */
    private function interfaceAttributes(array $attributes): array
    {
        return [
            'id' => $attributes['.id'] ?? null,
            'name' => $attributes['name'] ?? null,
            'type' => $attributes['type'] ?? null,
            'running' => ($attributes['running'] ?? 'false') === 'true',
            'disabled' => ($attributes['disabled'] ?? 'false') === 'true',
            'mtu' => $this->integer($attributes['mtu'] ?? null),
            'rx_bytes' => $this->integer($attributes['rx-byte'] ?? null),
            'tx_bytes' => $this->integer($attributes['tx-byte'] ?? null),
        ];
    }

    /** @param array<string, string> $attributes @return array<string, mixed> */
    private function ipAddressAttributes(array $attributes): array
    {
        return [
            'id' => $attributes['.id'] ?? null,
            'address' => $attributes['address'] ?? null,
            'network' => $attributes['network'] ?? null,
            'interface' => $attributes['interface'] ?? null,
            'disabled' => ($attributes['disabled'] ?? 'false') === 'true',
        ];
    }

    /** @param array<int, array<string, string>> $interfaces @return array{rx: int|null, tx: int|null} */
    private function trafficSummary(array $interfaces): array
    {
        $rx = 0;
        $tx = 0;
        $hasCounters = false;
        foreach ($interfaces as $interface) {
            $interfaceRx = $this->integer($interface['rx-byte'] ?? null);
            $interfaceTx = $this->integer($interface['tx-byte'] ?? null);
            if ($interfaceRx !== null) {
                $rx += $interfaceRx;
                $hasCounters = true;
            }
            if ($interfaceTx !== null) {
                $tx += $interfaceTx;
                $hasCounters = true;
            }
        }

        return ['rx' => $hasCounters ? $rx : null, 'tx' => $hasCounters ? $tx : null];
    }

    /** @param array<string, string> $health */
    private function temperature(array $health): ?float
    {
        foreach (['temperature', 'cpu-temperature', 'board-temperature'] as $key) {
            if (isset($health[$key])) {
                return $this->decimal(str_replace('C', '', $health[$key]));
            }
        }

        return null;
    }

    private function integer(?string $value): ?int
    {
        return $value !== null && preg_match('/^\d+$/', $value) === 1 ? (int) $value : null;
    }

    private function decimal(?string $value): ?float
    {
        return $value !== null && is_numeric($value) ? (float) $value : null;
    }
}
