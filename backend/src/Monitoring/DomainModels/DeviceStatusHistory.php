<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DomainModels;

final class DeviceStatusHistory
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $deviceType,
        public readonly int $deviceId,
        public readonly string $status,
        public readonly ?float $latencyMs,
        public readonly ?string $errorMessage,
        public readonly string $checkedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'device_type' => $this->deviceType,
            'device_id' => $this->deviceId,
            'status' => $this->status,
            'latency_ms' => $this->latencyMs,
            'error_message' => $this->errorMessage,
            'checked_at' => $this->checkedAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            deviceType: (string) $row['device_type'],
            deviceId: (int) $row['device_id'],
            status: (string) $row['status'],
            latencyMs: isset($row['latency_ms']) && $row['latency_ms'] !== null ? (float) $row['latency_ms'] : null,
            errorMessage: isset($row['error_message']) ? (string) $row['error_message'] : null,
            checkedAt: (string) $row['checked_at'],
        );
    }
}
