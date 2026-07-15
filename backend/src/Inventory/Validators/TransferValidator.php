<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Validators;

use SkyFi\Inventory\DTOs\TransferData;
use SkyFi\Shared\Exceptions\ValidationException;

final class TransferValidator
{
    public function validate(TransferData $data): void
    {
        $errors = [];
        if ($data->sourceWarehouseId < 1 || $data->destinationWarehouseId < 1) {
            $errors[] = $this->error('warehouses', 'Source and destination warehouses are required.');
        } elseif ($data->sourceWarehouseId === $data->destinationWarehouseId) {
            $errors[] = $this->error('destination_warehouse_id', 'Destination warehouse must differ from the source.');
        }
        if ($data->lines === []) {
            $errors[] = $this->error('lines', 'At least one transfer line is required.');
        }
        foreach ($data->lines as $index => $line) {
            if ((int) ($line['product_id'] ?? 0) < 1 || (int) ($line['source_location_id'] ?? 0) < 1 || (int) ($line['destination_location_id'] ?? 0) < 1) {
                $errors[] = $this->error("lines/{$index}", 'Product, source location, and destination location are required.');
            }
            if (!is_numeric($line['quantity_requested'] ?? null) || (float) $line['quantity_requested'] <= 0) {
                $errors[] = $this->error("lines/{$index}/quantity_requested", 'Requested quantity must be greater than zero.');
            }
            if (isset($line['asset_ids']) && !is_array($line['asset_ids'])) {
                $errors[] = $this->error("lines/{$index}/asset_ids", 'Asset IDs must be an array.');
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
