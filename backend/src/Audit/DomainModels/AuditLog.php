<?php

declare(strict_types=1);

namespace SkyFi\Audit\DomainModels;

final class AuditLog
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['old_values']) && is_string($row['old_values'])) {
            $row['old_values'] = json_decode($row['old_values'], true) ?: null;
        }
        if (isset($row['new_values']) && is_string($row['new_values'])) {
            $row['new_values'] = json_decode($row['new_values'], true) ?: null;
        }
        if (isset($row['compliance_tags']) && is_string($row['compliance_tags'])) {
            $row['compliance_tags'] = json_decode($row['compliance_tags'], true) ?: null;
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $row['entity_id'] = isset($row['entity_id']) ? (int) $row['entity_id'] : null;
        $row['is_immutable'] = isset($row['is_immutable']) ? (int) $row['is_immutable'] : 0;

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
