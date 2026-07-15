<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Validators;

use SkyFi\Purchasing\DTOs\PurchaseOrderData;
use SkyFi\Shared\Exceptions\ValidationException;

final class PurchaseOrderValidator
{
    public function validate(PurchaseOrderData $data): void
    {
        $errors = [];
        if ($data->vendorId < 1) {
            $errors[] = $this->error('vendor_id', 'A supplier is required.');
        }
        if ($data->taxRate < 0 || $data->taxRate > 100) {
            $errors[] = $this->error('tax_rate', 'Tax rate must be between 0 and 100.');
        }
        if ($data->discountAmount < 0) {
            $errors[] = $this->error('discount_amount', 'Discount amount cannot be negative.');
        }
        if (strlen($data->currency) < 2 || strlen($data->currency) > 3) {
            $errors[] = $this->error('currency', 'Currency code must be 2-3 characters.');
        }
        if ($data->orderDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->orderDate)) {
            $errors[] = $this->error('order_date', 'Order date must be in YYYY-MM-DD format.');
        }
        if ($data->expectedDeliveryDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->expectedDeliveryDate)) {
            $errors[] = $this->error('expected_delivery_date', 'Expected delivery date must be in YYYY-MM-DD format.');
        }
        if ($data->items === []) {
            $errors[] = $this->error('items', 'At least one line item is required.');
        }
        foreach ($data->items as $index => $item) {
            if ((int) ($item['product_id'] ?? 0) < 1) {
                $errors[] = $this->error("items/{$index}/product_id", 'A product is required.');
            }
            if (!is_numeric($item['quantity_ordered'] ?? null) || (float) $item['quantity_ordered'] <= 0) {
                $errors[] = $this->error("items/{$index}/quantity_ordered", 'Quantity ordered must be greater than zero.');
            }
            if (!is_numeric($item['unit_price'] ?? null) || (float) $item['unit_price'] < 0) {
                $errors[] = $this->error("items/{$index}/unit_price", 'Unit price cannot be negative.');
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
