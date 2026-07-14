<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

final class DeviceHealthCheckRequest
{
    public function __construct(
        public readonly string $deviceType,
        public readonly int $deviceId,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            deviceType: (string) ($payload['device_type'] ?? 'mikrotik_router'),
            deviceId: (int) ($payload['device_id'] ?? 0),
        );
    }
}
