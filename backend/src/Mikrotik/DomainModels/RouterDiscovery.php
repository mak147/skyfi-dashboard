<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DomainModels;

/** Normalized read-only RouterOS discovery data. */
final class RouterDiscovery
{
    /**
     * @param array<int, array<string, mixed>> $interfaces
     * @param array<int, array<string, mixed>> $ipAddresses
     */
    public function __construct(
        public readonly ?string $identity,
        public readonly ?string $routerosVersion,
        public readonly ?string $model,
        public readonly ?string $uptime,
        public readonly ?float $cpuUsagePercent,
        public readonly ?int $memoryTotalBytes,
        public readonly ?int $memoryFreeBytes,
        public readonly ?int $diskTotalBytes,
        public readonly ?int $diskFreeBytes,
        public readonly array $interfaces,
        public readonly array $ipAddresses,
        public readonly int $activeUsersCount,
        public readonly int $queueCount,
        public readonly float $latencyMs,
        public readonly string $discoveredAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'identity' => $this->identity,
            'routeros_version' => $this->routerosVersion,
            'model' => $this->model,
            'uptime' => $this->uptime,
            'cpu_usage_percent' => $this->cpuUsagePercent,
            'memory_total_bytes' => $this->memoryTotalBytes,
            'memory_free_bytes' => $this->memoryFreeBytes,
            'disk_total_bytes' => $this->diskTotalBytes,
            'disk_free_bytes' => $this->diskFreeBytes,
            'interfaces' => $this->interfaces,
            'ip_addresses' => $this->ipAddresses,
            'active_users_count' => $this->activeUsersCount,
            'queue_count' => $this->queueCount,
            'latency_ms' => $this->latencyMs,
            'discovered_at' => $this->discoveredAt,
        ];
    }
}
