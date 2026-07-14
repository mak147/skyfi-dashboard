<?php

declare(strict_types=1);

namespace SkyFi\Packages\Models;

final class PackagePrice
{
    /** @param array<string, mixed> $attributes */
    public function __construct(public readonly array $attributes) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
