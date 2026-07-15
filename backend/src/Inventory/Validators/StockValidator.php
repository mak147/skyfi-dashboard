<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Validators;

use SkyFi\Inventory\DTOs\StockOperationData;
use SkyFi\Shared\Exceptions\ValidationException;

final class StockValidator
{
    private const TYPES = ['opening_balance', 'stock_in', 'stock_out', 'adjustment_in', 'adjustment_out', 'return', 'damaged', 'scrap'];
    private const CONDITIONS = ['available', 'reserved', 'quarantine', 'damaged'];

    public function validate(StockOperationData $data): void
    {
        $errors = [];
        if (!in_array($data->type, self::TYPES, true)) {
            $errors[] = $this->error('type', 'Stock operation type is invalid.');
        }
        if ($data->lines === []) {
            $errors[] = $this->error('lines', 'At least one stock line is required.');
        }
        if (in_array($data->type, ['adjustment_in', 'adjustment_out', 'damaged', 'scrap'], true) && $data->reason === null) {
            $errors[] = $this->error('reason', 'A reason is required for this stock operation.');
        }
        foreach ($data->lines as $index => $line) {
            if ((int) ($line['product_id'] ?? 0) < 1) {
                $errors[] = $this->error("lines/{$index}/product_id", 'A product is required.');
            }
            if (!is_numeric($line['quantity'] ?? null) || (float) $line['quantity'] <= 0) {
                $errors[] = $this->error("lines/{$index}/quantity", 'Quantity must be greater than zero.');
            }
            if (isset($line['unit_cost']) && (!is_numeric($line['unit_cost']) || (float) $line['unit_cost'] < 0)) {
                $errors[] = $this->error("lines/{$index}/unit_cost", 'Unit cost cannot be negative.');
            }
            foreach (['source_condition', 'destination_condition'] as $condition) {
                if (isset($line[$condition]) && $line[$condition] !== '' && !in_array($line[$condition], self::CONDITIONS, true)) {
                    $errors[] = $this->error("lines/{$index}/{$condition}", 'Stock condition is invalid.');
                }
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
