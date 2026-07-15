<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\DomainModels;

final class PurchaseOrder
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function poNumber(): string
    {
        return (string) $this->attributes['po_number'];
    }

    public function status(): string
    {
        return (string) $this->attributes['status'];
    }

    public function totalAmount(): float
    {
        return (float) ($this->attributes['total_amount'] ?? 0);
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
