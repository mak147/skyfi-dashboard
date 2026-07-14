<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DTOs;

final class LogMonitoringEventData
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public readonly string $eventType,
        public readonly string $severity,
        public readonly string $sourceType,
        public readonly ?int $sourceId,
        public readonly string $message,
        public readonly ?array $metadata = null,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            eventType: (string) ($payload['event_type'] ?? 'device_status_change'),
            severity: (string) ($payload['severity'] ?? 'info'),
            sourceType: (string) ($payload['source_type'] ?? 'system'),
            sourceId: isset($payload['source_id']) && $payload['source_id'] !== null ? (int) $payload['source_id'] : null,
            message: (string) ($payload['message'] ?? ''),
            metadata: isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        );
    }
}
