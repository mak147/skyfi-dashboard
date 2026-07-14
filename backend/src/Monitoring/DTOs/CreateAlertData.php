<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

final class CreateAlertData
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public readonly string $alertType,
        public readonly string $severity,
        public readonly string $deviceType,
        public readonly ?int $deviceId,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $metricValue = null,
        public readonly ?string $thresholdValue = null,
        public readonly ?array $metadata = null,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            alertType: (string) ($payload['alert_type'] ?? ''),
            severity: (string) ($payload['severity'] ?? 'warning'),
            deviceType: (string) ($payload['device_type'] ?? 'mikrotik_router'),
            deviceId: isset($payload['device_id']) && $payload['device_id'] !== null ? (int) $payload['device_id'] : null,
            title: (string) ($payload['title'] ?? ''),
            description: isset($payload['description']) ? (string) $payload['description'] : null,
            metricValue: isset($payload['metric_value']) ? (string) $payload['metric_value'] : null,
            thresholdValue: isset($payload['threshold_value']) ? (string) $payload['threshold_value'] : null,
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        );
    }
}
