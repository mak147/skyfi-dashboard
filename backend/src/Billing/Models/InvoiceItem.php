<?php

declare(strict_types=1);

namespace SkyFi\Billing\Models;

final class InvoiceItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $invoiceId,
        public readonly string $itemType,
        public readonly string $description,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $amount,
        public readonly float $taxAmount,
        public readonly float $discountAmount,
        public readonly string $createdAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoiceId,
            'item_type' => $this->itemType,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'amount' => $this->amount,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            invoiceId: (int) $row['invoice_id'],
            itemType: (string) $row['item_type'],
            description: (string) $row['description'],
            quantity: (float) $row['quantity'],
            unitPrice: (float) $row['unit_price'],
            amount: (float) $row['amount'],
            taxAmount: (float) $row['tax_amount'],
            discountAmount: (float) $row['discount_amount'],
            createdAt: (string) $row['created_at'],
        );
    }
}
