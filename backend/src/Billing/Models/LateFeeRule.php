<?php

declare(strict_types=1);

namespace SkyFi\Billing\Models;

final class LateFeeRule
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $daysAfterDue,
        public readonly string $feeType,
        public readonly float $feeAmount,
        public readonly bool $isActive,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'days_after_due' => $this->daysAfterDue,
            'fee_type' => $this->feeType,
            'fee_amount' => $this->feeAmount,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            daysAfterDue: (int) $row['days_after_due'],
            feeType: (string) $row['fee_type'],
            feeAmount: (float) $row['fee_amount'],
            isActive: (bool) $row['is_active'],
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
