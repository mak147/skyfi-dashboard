<?php

declare(strict_types=1);

namespace SkyFi\Integration\DomainModels;

final class Webhook
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['events']) && is_string($row['events'])) {
            $row['events'] = json_decode($row['events'], true) ?: [];
        }
        if (isset($row['retry_policy']) && is_string($row['retry_policy'])) {
            $row['retry_policy'] = json_decode($row['retry_policy'], true) ?: [];
        }
        if (isset($row['filter_rules']) && is_string($row['filter_rules'])) {
            $row['filter_rules'] = json_decode($row['filter_rules'], true);
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['client_application_id'] = isset($row['client_application_id']) ? (int) $row['client_application_id'] : null;
        $row['is_active'] = (bool) ($row['is_active'] ?? true);
        $row['is_inbound'] = (bool) ($row['is_inbound'] ?? false);
        $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;

        return new self($row);
    }

    public function id(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = $this->attributes;
        unset($out['secret'], $out['inbound_secret']);

        return $out;
    }

    /** @return array<string, mixed> */
    public function toArrayWithSecrets(): array
    {
        return $this->attributes;
    }
}
