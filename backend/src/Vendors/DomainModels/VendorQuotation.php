<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DomainModels;

final class VendorQuotation
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function vendorId(): int
    {
        return (int) $this->attributes['vendor_id'];
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

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
