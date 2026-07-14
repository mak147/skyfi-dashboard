<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DomainModels;

final class MonitoringEvent
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public readonly ?int $id,
        public readonly string $eventType,
        public readonly string $severity,
        public readonly string $sourceType,
        public readonly ?int $sourceId,
        public readonly string $message,
        public readonly ?array $metadata,
        public readonly string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->eventType,
            'severity' => $this->severity,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $metadata = null;
        if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
            $decoded = json_decode($row['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        } elseif (isset($row['metadata']) && is_array($row['metadata'])) {
            $metadata = $row['metadata'];
        }

        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            eventType: (string) $row['event_type'],
            severity: (string) $row['severity'],
            sourceType: (string) $row['source_type'],
            sourceId: isset($row['source_id']) ? (int) $row['source_id'] : null,
            message: (string) $row['message'],
            metadata: $metadata,
            createdAt: (string) $row['created_at'],
        );
    }
}
