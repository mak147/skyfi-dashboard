<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\DomainModels;

final class NetworkAlert
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public readonly ?int $id,
        public readonly string $alertType,
        public readonly string $severity,
        public readonly string $status,
        public readonly string $deviceType,
        public readonly ?int $deviceId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $metricValue,
        public readonly ?string $thresholdValue,
        public readonly ?array $metadata,
        public readonly string $triggeredAt,
        public readonly ?string $acknowledgedAt,
        public readonly ?int $acknowledgedBy,
        public readonly ?string $resolvedAt,
        public readonly ?int $resolvedBy,
        public readonly ?string $dismissedAt,
        public readonly ?int $dismissedBy,
        public readonly ?string $resolutionNotes,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'alert_type' => $this->alertType,
            'severity' => $this->severity,
            'status' => $this->status,
            'device_type' => $this->deviceType,
            'device_id' => $this->deviceId,
            'title' => $this->title,
            'description' => $this->description,
            'metric_value' => $this->metricValue,
            'threshold_value' => $this->thresholdValue,
            'metadata' => $this->metadata,
            'triggered_at' => $this->triggeredAt,
            'acknowledged_at' => $this->acknowledgedAt,
            'acknowledged_by' => $this->acknowledgedBy,
            'resolved_at' => $this->resolvedAt,
            'resolved_by' => $this->resolvedBy,
            'dismissed_at' => $this->dismissedAt,
            'dismissed_by' => $this->dismissedBy,
            'resolution_notes' => $this->resolutionNotes,
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
            alertType: (string) $row['alert_type'],
            severity: (string) $row['severity'],
            status: (string) $row['status'],
            deviceType: (string) $row['device_type'],
            deviceId: isset($row['device_id']) && $row['device_id'] !== null ? (int) $row['device_id'] : null,
            title: (string) $row['title'],
            description: isset($row['description']) ? (string) $row['description'] : null,
            metricValue: isset($row['metric_value']) ? (string) $row['metric_value'] : null,
            thresholdValue: isset($row['threshold_value']) ? (string) $row['threshold_value'] : null,
            metadata: $metadata,
            triggeredAt: (string) $row['triggered_at'],
            acknowledgedAt: isset($row['acknowledged_at']) ? (string) $row['acknowledged_at'] : null,
            acknowledgedBy: isset($row['acknowledged_by']) && $row['acknowledged_by'] !== null ? (int) $row['acknowledged_by'] : null,
            resolvedAt: isset($row['resolved_at']) ? (string) $row['resolved_at'] : null,
            resolvedBy: isset($row['resolved_by']) && $row['resolved_by'] !== null ? (int) $row['resolved_by'] : null,
            dismissedAt: isset($row['dismissed_at']) ? (string) $row['dismissed_at'] : null,
            dismissedBy: isset($row['dismissed_by']) && $row['dismissed_by'] !== null ? (int) $row['dismissed_by'] : null,
            resolutionNotes: isset($row['resolution_notes']) ? (string) $row['resolution_notes'] : null,
        );
    }
}
