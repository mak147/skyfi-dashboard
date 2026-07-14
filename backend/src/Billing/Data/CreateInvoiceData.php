<?php

declare(strict_types=1);

namespace SkyFi\Billing\Data;

use SkyFi\Shared\Exceptions\ValidationException;

final class CreateInvoiceData
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public readonly int $customerId,
        public readonly int $connectionId,
        public readonly int $packageId,
        public readonly string $billingPeriodStart,
        public readonly string $billingPeriodEnd,
        public readonly string $issueDate,
        public readonly string $dueDate,
        public readonly ?string $notes,
        public readonly array $items,
        public readonly float $previousBalance,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $errors = [];

        $customerId = isset($data['customer_id']) && is_numeric($data['customer_id']) ? (int) $data['customer_id'] : 0;
        if ($customerId <= 0) {
            $errors[] = ['code' => 'required', 'detail' => 'Customer is required.', 'source' => ['pointer' => '/data/attributes/customer_id']];
        }

        $connectionId = isset($data['connection_id']) && is_numeric($data['connection_id']) ? (int) $data['connection_id'] : 0;
        if ($connectionId <= 0) {
            $errors[] = ['code' => 'required', 'detail' => 'Connection is required.', 'source' => ['pointer' => '/data/attributes/connection_id']];
        }

        $packageId = isset($data['package_id']) && is_numeric($data['package_id']) ? (int) $data['package_id'] : 0;
        if ($packageId <= 0) {
            $errors[] = ['code' => 'required', 'detail' => 'Package is required.', 'source' => ['pointer' => '/data/attributes/package_id']];
        }

        $billingPeriodStart = isset($data['billing_period_start']) && is_string($data['billing_period_start']) ? trim($data['billing_period_start']) : '';
        if ($billingPeriodStart === '' || !self::isValidDate($billingPeriodStart)) {
            $errors[] = ['code' => 'required', 'detail' => 'Billing period start is required and must be a valid date.', 'source' => ['pointer' => '/data/attributes/billing_period_start']];
        }

        $billingPeriodEnd = isset($data['billing_period_end']) && is_string($data['billing_period_end']) ? trim($data['billing_period_end']) : '';
        if ($billingPeriodEnd === '' || !self::isValidDate($billingPeriodEnd)) {
            $errors[] = ['code' => 'required', 'detail' => 'Billing period end is required and must be a valid date.', 'source' => ['pointer' => '/data/attributes/billing_period_end']];
        }

        $issueDate = isset($data['issue_date']) && is_string($data['issue_date']) ? trim($data['issue_date']) : '';
        if ($issueDate === '' || !self::isValidDate($issueDate)) {
            $errors[] = ['code' => 'required', 'detail' => 'Issue date is required and must be a valid date.', 'source' => ['pointer' => '/data/attributes/issue_date']];
        }

        $dueDate = isset($data['due_date']) && is_string($data['due_date']) ? trim($data['due_date']) : '';
        if ($dueDate === '' || !self::isValidDate($dueDate)) {
            $errors[] = ['code' => 'required', 'detail' => 'Due date is required and must be a valid date.', 'source' => ['pointer' => '/data/attributes/due_date']];
        }

        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $itemType = isset($item['item_type']) && is_string($item['item_type']) ? $item['item_type'] : 'custom';
                $description = isset($item['description']) && is_string($item['description']) ? trim($item['description']) : '';
                if ($description === '') {
                    $errors[] = ['code' => 'required', 'detail' => 'Item description is required.', 'source' => ['pointer' => "/data/attributes/items/{$index}/description"]];
                }
                $quantity = isset($item['quantity']) && is_numeric($item['quantity']) ? (float) $item['quantity'] : 1.0;
                $unitPrice = isset($item['unit_price']) && is_numeric($item['unit_price']) ? (float) $item['unit_price'] : 0.0;
                $amount = $quantity * $unitPrice;
                $items[] = [
                    'item_type' => $itemType,
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'tax_amount' => isset($item['tax_amount']) && is_numeric($item['tax_amount']) ? (float) $item['tax_amount'] : 0.0,
                    'discount_amount' => isset($item['discount_amount']) && is_numeric($item['discount_amount']) ? (float) $item['discount_amount'] : 0.0,
                ];
            }
        }

        if ($items === []) {
            $errors[] = ['code' => 'required', 'detail' => 'At least one invoice item is required.', 'source' => ['pointer' => '/data/attributes/items']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self(
            customerId: $customerId,
            connectionId: $connectionId,
            packageId: $packageId,
            billingPeriodStart: $billingPeriodStart,
            billingPeriodEnd: $billingPeriodEnd,
            issueDate: $issueDate,
            dueDate: $dueDate,
            notes: isset($data['notes']) && is_string($data['notes']) && $data['notes'] !== '' ? trim($data['notes']) : null,
            items: $items,
            previousBalance: isset($data['previous_balance']) && is_numeric($data['previous_balance']) ? (float) $data['previous_balance'] : 0.0,
        );
    }

    private static function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
