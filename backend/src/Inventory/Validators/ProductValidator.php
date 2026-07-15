<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Validators;

use SkyFi\Inventory\DTOs\ProductData;
use SkyFi\Shared\Exceptions\ValidationException;

final class ProductValidator
{
    public function validate(ProductData $data): void
    {
        $errors = [];
        if ($data->categoryId < 1) {
            $errors[] = $this->error('category_id', 'A product category is required.');
        }
        if ($data->unitId < 1) {
            $errors[] = $this->error('unit_id', 'A unit of measure is required.');
        }
        if ($data->sku === '' || strlen($data->sku) > 80 || !preg_match('/^[A-Z0-9._-]+$/', $data->sku)) {
            $errors[] = $this->error('sku', 'SKU is required and may contain only letters, numbers, dots, underscores, and hyphens.');
        }
        if ($data->name === '' || mb_strlen($data->name) > 200) {
            $errors[] = $this->error('name', 'Name is required and must not exceed 200 characters.');
        }
        if (!in_array($data->trackingMode, ['quantity', 'serialized'], true)) {
            $errors[] = $this->error('tracking_mode', 'Tracking mode must be quantity or serialized.');
        }
        if (!in_array($data->status, ['active', 'inactive', 'discontinued'], true)) {
            $errors[] = $this->error('status', 'Product status is invalid.');
        }
        foreach (['standardCost' => $data->standardCost, 'minimumStock' => $data->minimumStock, 'reorderLevel' => $data->reorderLevel] as $field => $value) {
            if (!is_numeric($value) || (float) $value < 0) {
                $errors[] = $this->error($this->snake($field), 'Value must be zero or greater.');
            }
        }
        if ($data->barcode !== null && strlen($data->barcode) > 100) {
            $errors[] = $this->error('barcode', 'Barcode must not exceed 100 characters.');
        }
        foreach ($data->vendors as $vendor) {
            if ((int) ($vendor['vendor_id'] ?? 0) < 1) {
                $errors[] = $this->error('vendors', 'Every product vendor must reference a valid vendor.');
                break;
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

    private function snake(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}
