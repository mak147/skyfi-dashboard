<?php

declare(strict_types=1);

namespace SkyFi\Packages\Models;

final class InternetPackage
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function status(): string
    {
        return (string) $this->attributes['status'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
