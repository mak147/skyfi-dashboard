<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\DomainModels;

final class SupplierInvoice
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function invoiceNumber(): string
    {
        return (string) $this->attributes['invoice_number'];
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
