<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DomainModels;

final class Vendor
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function code(): string
    {
        return (string) $this->attributes['code'];
    }

    public function name(): string
    {
        return (string) $this->attributes['name'];
    }

    public function status(): string
    {
        return (string) $this->attributes['status'];
    }

    public function overallRating(): float
    {
        return (float) ($this->attributes['overall_rating'] ?? 0.0);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
