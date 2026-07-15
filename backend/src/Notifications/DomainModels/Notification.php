<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DomainModels;

final class Notification
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['data']) && is_string($row['data'])) {
            $row['data'] = json_decode($row['data'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['recipient_user_id'] = (int) ($row['recipient_user_id'] ?? 0);
        $row['event_id'] = isset($row['event_id']) ? (int) $row['event_id'] : null;
        $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;

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
