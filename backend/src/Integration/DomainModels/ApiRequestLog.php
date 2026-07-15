<?php

declare(strict_types=1);

namespace SkyFi\Integration\DomainModels;

final class ApiRequestLog
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['request_headers']) && is_string($row['request_headers'])) {
            $row['request_headers'] = json_decode($row['request_headers'], true) ?: [];
        }
        if (isset($row['request_body']) && is_string($row['request_body'])) {
            $row['request_body'] = json_decode($row['request_body'], true) ?: [];
        }
        if (isset($row['response_body']) && is_string($row['response_body'])) {
            $row['response_body'] = json_decode($row['response_body'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['api_key_id'] = isset($row['api_key_id']) ? (int) $row['api_key_id'] : null;
        $row['client_application_id'] = isset($row['client_application_id']) ? (int) $row['client_application_id'] : null;
        $row['status_code'] = (int) ($row['status_code'] ?? 0);
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
