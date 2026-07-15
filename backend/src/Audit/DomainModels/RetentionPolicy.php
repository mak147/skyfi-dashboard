<?php

declare(strict_types=1);

namespace SkyFi\Audit\DomainModels;

final class RetentionPolicy
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['retention_days'] = (int) ($row['retention_days'] ?? 365);
        $row['auto_archive'] = isset($row['auto_archive']) ? (int) $row['auto_archive'] : 0;
        $row['is_active'] = isset($row['is_active']) ? (int) $row['is_active'] : 1;
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
