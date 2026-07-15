<?php

declare(strict_types=1);

namespace SkyFi\Integration\DomainModels;

final class EventRegistryEntry
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['payload_schema']) && is_string($row['payload_schema'])) {
            $row['payload_schema'] = json_decode($row['payload_schema'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_active'] = (bool) ($row['is_active'] ?? true);

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
