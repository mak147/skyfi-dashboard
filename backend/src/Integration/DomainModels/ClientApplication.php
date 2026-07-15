<?php

declare(strict_types=1);

namespace SkyFi\Integration\DomainModels;

final class ClientApplication
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['redirect_uris']) && is_string($row['redirect_uris'])) {
            $row['redirect_uris'] = json_decode($row['redirect_uris'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_active'] = (bool) ($row['is_active'] ?? true);
        $row['rate_limit_per_minute'] = (int) ($row['rate_limit_per_minute'] ?? 60);
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
