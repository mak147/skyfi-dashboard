<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DomainModels;

final class NotificationEvent
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['payload']) && is_string($row['payload'])) {
            $row['payload'] = json_decode($row['payload'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);

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
