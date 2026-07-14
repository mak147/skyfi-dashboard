<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DomainModels;

final class RouterHealthSnapshot
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $routerId,
        public readonly string $status,
        public readonly ?float $latencyMs,
        public readonly ?float $cpuUsagePercent,
        public readonly ?int $memoryTotalBytes,
        public readonly ?int $memoryFreeBytes,
        public readonly ?int $diskTotalBytes,
        public readonly ?int $diskFreeBytes,
        public readonly ?float $temperatureCelsius,
        public readonly ?int $trafficRxBytes,
        public readonly ?int $trafficTxBytes,
        public readonly ?int $activeUsersCount,
        public readonly ?int $queueCount,
        public readonly ?string $uptime,
        public readonly ?string $errorMessage,
        public readonly string $checkedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'router_id' => $this->routerId,
            'status' => $this->status,
            'latency_ms' => $this->latencyMs,
            'cpu_usage_percent' => $this->cpuUsagePercent,
            'memory_total_bytes' => $this->memoryTotalBytes,
            'memory_free_bytes' => $this->memoryFreeBytes,
            'disk_total_bytes' => $this->diskTotalBytes,
            'disk_free_bytes' => $this->diskFreeBytes,
            'temperature_celsius' => $this->temperatureCelsius,
            'traffic_rx_bytes' => $this->trafficRxBytes,
            'traffic_tx_bytes' => $this->trafficTxBytes,
            'active_users_count' => $this->activeUsersCount,
            'queue_count' => $this->queueCount,
            'uptime' => $this->uptime,
            'error_message' => $this->errorMessage,
            'checked_at' => $this->checkedAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            routerId: (int) $row['router_id'],
            status: (string) $row['status'],
            latencyMs: isset($row['latency_ms']) ? (float) $row['latency_ms'] : null,
            cpuUsagePercent: isset($row['cpu_usage_percent']) ? (float) $row['cpu_usage_percent'] : null,
            memoryTotalBytes: isset($row['memory_total_bytes']) ? (int) $row['memory_total_bytes'] : null,
            memoryFreeBytes: isset($row['memory_free_bytes']) ? (int) $row['memory_free_bytes'] : null,
            diskTotalBytes: isset($row['disk_total_bytes']) ? (int) $row['disk_total_bytes'] : null,
            diskFreeBytes: isset($row['disk_free_bytes']) ? (int) $row['disk_free_bytes'] : null,
            temperatureCelsius: isset($row['temperature_celsius']) ? (float) $row['temperature_celsius'] : null,
            trafficRxBytes: isset($row['traffic_rx_bytes']) ? (int) $row['traffic_rx_bytes'] : null,
            trafficTxBytes: isset($row['traffic_tx_bytes']) ? (int) $row['traffic_tx_bytes'] : null,
            activeUsersCount: isset($row['active_users_count']) ? (int) $row['active_users_count'] : null,
            queueCount: isset($row['queue_count']) ? (int) $row['queue_count'] : null,
            uptime: $row['uptime'] ?? null,
            errorMessage: $row['error_message'] ?? null,
            checkedAt: (string) $row['checked_at'],
        );
    }
}
