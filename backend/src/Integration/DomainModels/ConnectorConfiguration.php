<?php

declare(strict_types=1);

namespace SkyFi\Integration\DomainModels;

final class ConnectorConfiguration
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        if (isset($row['config']) && is_string($row['config'])) {
            $row['config'] = json_decode($row['config'], true) ?: [];
        }
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_enabled'] = (bool) ($row['is_enabled'] ?? false);
        $row['rate_limit_per_minute'] = isset($row['rate_limit_per_minute']) ? (int) $row['rate_limit_per_minute'] : null;
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
        $out = $this->attributes;
        // Mask sensitive config fields
        if (isset($out['config']) && is_array($out['config'])) {
            foreach ($out['config'] as $key => $value) {
                if (is_string($value) && $value !== '' && preg_match('/(key|secret|password|token|salt|hash)/i', $key)) {
                    $out['config'][$key] = '••••••••';
                }
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    public function toArrayFull(): array
    {
        return $this->attributes;
    }
}
