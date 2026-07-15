<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DomainModels;

final class NotificationTemplate
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['variables']) && is_string($row['variables'])) {
            $row['variables'] = json_decode($row['variables'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_transactional'] = (int) ($row['is_transactional'] ?? 0);
        $row['is_active'] = (int) ($row['is_active'] ?? 0);

        return new self($row);
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
