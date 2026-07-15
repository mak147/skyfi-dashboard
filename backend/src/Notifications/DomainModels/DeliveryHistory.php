<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DomainModels;

final class DeliveryHistory
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['notification_id'] = isset($row['notification_id']) ? (int) $row['notification_id'] : null;
        $row['event_id'] = isset($row['event_id']) ? (int) $row['event_id'] : null;
        $row['recipient_user_id'] = isset($row['recipient_user_id']) ? (int) $row['recipient_user_id'] : null;
        $row['template_id'] = isset($row['template_id']) ? (int) $row['template_id'] : null;
        $row['attempt_count'] = (int) ($row['attempt_count'] ?? 0);

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
