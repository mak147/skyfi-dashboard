<?php

declare(strict_types=1);

namespace SkyFi\Audit\DomainModels;

final class ActivityEvent
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['metadata']) && is_string($row['metadata'])) {
            $row['metadata'] = json_decode($row['metadata'], true) ?: null;
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $row['resource_id'] = isset($row['resource_id']) ? (int) $row['resource_id'] : null;

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
