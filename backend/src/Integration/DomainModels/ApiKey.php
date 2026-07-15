<?php

declare(strict_types=1);

namespace SkyFi\Integration\DomainModels;

final class ApiKey
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['scopes']) && is_string($row['scopes'])) {
            $row['scopes'] = json_decode($row['scopes'], true) ?: [];
        }
        if (isset($row['ip_allow_list']) && is_string($row['ip_allow_list'])) {
            $row['ip_allow_list'] = json_decode($row['ip_allow_list'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['client_application_id'] = isset($row['client_application_id']) ? (int) $row['client_application_id'] : null;
        $row['is_active'] = (bool) ($row['is_active'] ?? true);
        $row['rate_limit_per_minute'] = isset($row['rate_limit_per_minute']) ? (int) $row['rate_limit_per_minute'] : null;
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
        return $this->attributes;
    }
}
