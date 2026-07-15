<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DomainModels;

final class UserNotificationPreference
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['user_id'] = (int) ($row['user_id'] ?? 0);
        $row['is_enabled'] = (int) ($row['is_enabled'] ?? 0);

        return new self($row);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
