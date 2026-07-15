<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Validators;

use SkyFi\Purchasing\DTOs\GoodsReceiptData;
use SkyFi\Shared\Exceptions\ValidationException;

final class GoodsReceiptValidator
{
    private const CONDITIONS = ['available', 'reserved', 'quarantine', 'damaged'];

    public function validate(GoodsReceiptData $data): void
    {
        $errors = [];
        if ($data->purchaseOrderId < 1) {
            $errors[] = $this->error('purchase_order_id', 'A purchase order is required.');
        }
        if ($data->items === []) {
            $errors[] = $this->error('items', 'At least one receipt line is required.');
        }
        foreach ($data->items as $index => $item) {
            if ((int) ($item['purchase_order_item_id'] ?? 0) < 1) {
                $errors[] = $this->error("items/{$index}/purchase_order_item_id", 'A purchase order item is required.');
            }
            if (!is_numeric($item['quantity_accepted'] ?? null) || (float) $item['quantity_accepted'] < 0) {
                $errors[] = $this->error("items/{$index}/quantity_accepted", 'Accepted quantity cannot be negative.');
            }
            if (isset($item['quantity_damaged']) && (!is_numeric($item['quantity_damaged']) || (float) $item['quantity_damaged'] < 0)) {
                $errors[] = $this->error("items/{$index}/quantity_damaged", 'Damaged quantity cannot be negative.');
            }
            if (isset($item['quantity_short']) && (!is_numeric($item['quantity_short']) || (float) $item['quantity_short'] < 0)) {
                $errors[] = $this->error("items/{$index}/quantity_short", 'Short quantity cannot be negative.');
            }
            if (isset($item['condition']) && !in_array($item['condition'], self::CONDITIONS, true)) {
                $errors[] = $this->error("items/{$index}/condition", 'Stock condition is invalid.');
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @return array<string, mixed> */
    private function error(string $field, string $detail): array
    {
        return ['code' => 'validation_error', 'detail' => $detail, 'source' => ['pointer' => '/data/attributes/' . $field]];
    }
}
