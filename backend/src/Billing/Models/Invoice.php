<?php

declare(strict_types=1);

namespace SkyFi\Billing\Models;

final class Invoice
{
    /**
     * @param array<InvoiceItem> $items
     * @param array<string, mixed>|null $raw
     */
    public function __construct(
        public readonly int $id,
        public readonly string $invoiceNumber,
        public readonly int $customerId,
        public readonly int $connectionId,
        public readonly int $packageId,
        public readonly string $status,
        public readonly string $billingPeriodStart,
        public readonly string $billingPeriodEnd,
        public readonly string $issueDate,
        public readonly string $dueDate,
        public readonly string $currency,
        public readonly float $subtotal,
        public readonly float $taxAmount,
        public readonly float $discountAmount,
        public readonly float $lateFeeAmount,
        public readonly float $previousBalance,
        public readonly float $totalAmount,
        public readonly float $balanceDue,
        public readonly ?string $notes,
        public readonly int $createdBy,
        public readonly ?int $updatedBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $deletedAt,
        public readonly ?string $customerName = null,
        public readonly ?string $customerCode = null,
        public readonly ?string $connectionNumber = null,
        public readonly ?string $packageName = null,
        public readonly array $items = [],
        public readonly array $activities = [],
        public readonly ?array $raw = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoiceNumber,
            'customer_id' => $this->customerId,
            'connection_id' => $this->connectionId,
            'package_id' => $this->packageId,
            'status' => $this->status,
            'billing_period_start' => $this->billingPeriodStart,
            'billing_period_end' => $this->billingPeriodEnd,
            'issue_date' => $this->issueDate,
            'due_date' => $this->dueDate,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'late_fee_amount' => $this->lateFeeAmount,
            'previous_balance' => $this->previousBalance,
            'total_amount' => $this->totalAmount,
            'balance_due' => $this->balanceDue,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
            'customer_name' => $this->customerName,
            'customer_code' => $this->customerCode,
            'connection_number' => $this->connectionNumber,
            'package_name' => $this->packageName,
            'items' => array_map(static fn(InvoiceItem $item): array => $item->toArray(), $this->items),
            'activities' => $this->activities,
        ];
    }

    /** Hydrate from a database row. */
    public static function fromRow(array $row, array $items = [], array $activities = []): self
    {
        return new self(
            id: (int) $row['id'],
            invoiceNumber: (string) $row['invoice_number'],
            customerId: (int) $row['customer_id'],
            connectionId: (int) $row['connection_id'],
            packageId: (int) $row['package_id'],
            status: (string) $row['status'],
            billingPeriodStart: (string) $row['billing_period_start'],
            billingPeriodEnd: (string) $row['billing_period_end'],
            issueDate: (string) $row['issue_date'],
            dueDate: (string) $row['due_date'],
            currency: (string) $row['currency'],
            subtotal: (float) $row['subtotal'],
            taxAmount: (float) $row['tax_amount'],
            discountAmount: (float) $row['discount_amount'],
            lateFeeAmount: (float) $row['late_fee_amount'],
            previousBalance: (float) $row['previous_balance'],
            totalAmount: (float) $row['total_amount'],
            balanceDue: (float) $row['balance_due'],
            notes: $row['notes'] ?? null,
            createdBy: (int) $row['created_by'],
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            deletedAt: $row['deleted_at'] ?? null,
            customerName: $row['customer_name'] ?? null,
            customerCode: $row['customer_code'] ?? null,
            connectionNumber: $row['connection_number'] ?? null,
            packageName: $row['package_name'] ?? null,
            items: $items,
            activities: $activities,
            raw: $row,
        );
    }
}
