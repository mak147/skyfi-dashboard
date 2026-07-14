<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DomainModels;

final class RouterGroup
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        $attributes['id'] = $this->id();
        if (isset($attributes['router_count'])) {
            $attributes['router_count'] = (int) $attributes['router_count'];
        }

        return $attributes;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
