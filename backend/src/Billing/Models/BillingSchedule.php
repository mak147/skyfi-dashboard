<?php

declare(strict_types=1);

namespace SkyFi\Billing\Models;

final class BillingSchedule
{
    public function __construct(
        public readonly int $id,
        public readonly int $connectionId,
        public readonly string $billingCycle,
        public readonly ?int $customIntervalDays,
        public readonly string $anchorDate,
        public readonly string $nextBillDate,
        public readonly int $gracePeriodDays,
        public readonly bool $autoGenerate,
        public readonly bool $prorationEnabled,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $connectionNumber = null,
        public readonly ?string $customerName = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'connection_id' => $this->connectionId,
            'billing_cycle' => $this->billingCycle,
            'custom_interval_days' => $this->customIntervalDays,
            'anchor_date' => $this->anchorDate,
            'next_bill_date' => $this->nextBillDate,
            'grace_period_days' => $this->gracePeriodDays,
            'auto_generate' => $this->autoGenerate,
            'proration_enabled' => $this->prorationEnabled,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'connection_number' => $this->connectionNumber,
            'customer_name' => $this->customerName,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            connectionId: (int) $row['connection_id'],
            billingCycle: (string) $row['billing_cycle'],
            customIntervalDays: isset($row['custom_interval_days']) ? (int) $row['custom_interval_days'] : null,
            anchorDate: (string) $row['anchor_date'],
            nextBillDate: (string) $row['next_bill_date'],
            gracePeriodDays: (int) $row['grace_period_days'],
            autoGenerate: (bool) $row['auto_generate'],
            prorationEnabled: (bool) $row['proration_enabled'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
            connectionNumber: $row['connection_number'] ?? null,
            customerName: $row['customer_name'] ?? null,
        );
    }
}
