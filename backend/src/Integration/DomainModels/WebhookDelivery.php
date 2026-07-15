<?php

declare(strict_types=1);

namespace SkyFi\Integration\DomainModels;

final class WebhookDelivery
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['payload']) && is_string($row['payload'])) {
            $row['payload'] = json_decode($row['payload'], true) ?: [];
        }
        if (isset($row['request_headers']) && is_string($row['request_headers'])) {
            $row['request_headers'] = json_decode($row['request_headers'], true) ?: [];
        }
        if (isset($row['response_headers']) && is_string($row['response_headers'])) {
            $row['response_headers'] = json_decode($row['response_headers'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['webhook_id'] = (int) ($row['webhook_id'] ?? 0);
        $row['event_id'] = isset($row['event_id']) ? (int) $row['event_id'] : null;
        $row['attempt_number'] = (int) ($row['attempt_number'] ?? 1);
        $row['response_status_code'] = isset($row['response_status_code']) ? (int) $row['response_status_code'] : null;
        $row['duration_ms'] = isset($row['duration_ms']) ? (int) $row['duration_ms'] : null;

        return new self($row);
    }

    public function id(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
