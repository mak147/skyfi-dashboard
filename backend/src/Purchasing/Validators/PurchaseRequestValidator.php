<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Validators;

use SkyFi\Purchasing\DTOs\PurchaseRequestData;
use SkyFi\Shared\Exceptions\ValidationException;

final class PurchaseRequestValidator
{
    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function validate(PurchaseRequestData $data): void
    {
        $errors = [];
        if ($data->requesterUserId < 1) {
            $errors[] = $this->error('requester_user_id', 'A requester is required.');
        }
        if (!in_array($data->priority, self::PRIORITIES, true)) {
            $errors[] = $this->error('priority', 'Priority must be low, normal, high, or urgent.');
        }
        if ($data->requiredDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->requiredDate)) {
            $errors[] = $this->error('required_date', 'Required date must be in YYYY-MM-DD format.');
        }
        if ($data->items === []) {
            $errors[] = $this->error('items', 'At least one line item is required.');
        }
        foreach ($data->items as $index => $item) {
            if ((int) ($item['product_id'] ?? 0) < 1) {
                $errors[] = $this->error("items/{$index}/product_id", 'A product is required.');
            }
            if (!is_numeric($item['quantity'] ?? null) || (float) $item['quantity'] <= 0) {
                $errors[] = $this->error("items/{$index}/quantity", 'Quantity must be greater than zero.');
            }
            if (isset($item['estimated_unit_cost']) && (!is_numeric($item['estimated_unit_cost']) || (float) $item['estimated_unit_cost'] < 0)) {
                $errors[] = $this->error("items/{$index}/estimated_unit_cost", 'Estimated unit cost cannot be negative.');
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
