<?php

declare(strict_types=1);

namespace SkyFi\Billing\Data;

use SkyFi\Shared\Exceptions\ValidationException;

final class UpdateInvoiceData
{
    /**
     * @param array<int, array<string, mixed>>|null $items
     */
    public function __construct(
        public readonly ?string $notes,
        public readonly ?string $dueDate,
        public readonly ?array $items,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $errors = [];
        $dueDate = null;

        if (isset($data['due_date']) && is_string($data['due_date']) && $data['due_date'] !== '') {
            $dueDate = trim($data['due_date']);
            $d = \DateTime::createFromFormat('Y-m-d', $dueDate);
            if ($d === false || $d->format('Y-m-d') !== $dueDate) {
                $errors[] = ['code' => 'invalid_date', 'detail' => 'Due date must be a valid date.', 'source' => ['pointer' => '/data/attributes/due_date']];
            }
        }

        $items = null;
        if (isset($data['items']) && is_array($data['items'])) {
            $items = [];
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

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self(
            notes: isset($data['notes']) && is_string($data['notes']) && $data['notes'] !== '' ? trim($data['notes']) : null,
            dueDate: $dueDate,
            items: $items,
        );
    }
}
