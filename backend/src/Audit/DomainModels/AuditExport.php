<?php

declare(strict_types=1);

namespace SkyFi\Audit\DomainModels;

final class AuditExport
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['filters']) && is_string($row['filters'])) {
            $row['filters'] = json_decode($row['filters'], true) ?: null;
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['user_id'] = (int) ($row['user_id'] ?? 0);
        $row['row_count'] = (int) ($row['row_count'] ?? 0);

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
