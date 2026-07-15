<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Validators;

use SkyFi\Inventory\DTOs\WarehouseData;
use SkyFi\Shared\Exceptions\ValidationException;

final class WarehouseValidator
{
    public function validate(WarehouseData $data): void
    {
        $errors = [];
        if ($data->code === '' || strlen($data->code) > 50 || !preg_match('/^[A-Z0-9_-]+$/', $data->code)) {
            $errors[] = $this->error('code', 'Warehouse code is required and may contain uppercase letters, numbers, underscores, and hyphens.');
        }
        if ($data->name === '' || mb_strlen($data->name) > 150) {
            $errors[] = $this->error('name', 'Warehouse name is required and must not exceed 150 characters.');
        }
        if (!in_array($data->type, ['main', 'branch', 'technician_vehicle', 'repair_depot', 'site_store', 'other'], true)) {
            $errors[] = $this->error('type', 'Warehouse type is invalid.');
        }
        if (!in_array($data->status, ['active', 'inactive', 'maintenance', 'closed'], true)) {
            $errors[] = $this->error('status', 'Warehouse status is invalid.');
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @param array<string, mixed> $data */
    public function validateLocation(array $data): void
    {
        $errors = [];
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        if ($code === '' || strlen($code) > 50 || !preg_match('/^[A-Z0-9_-]+$/', $code)) {
            $errors[] = $this->error('code', 'Location code is required and has an invalid format.');
        }
        if ($name === '' || mb_strlen($name) > 120) {
            $errors[] = $this->error('name', 'Location name is required and must not exceed 120 characters.');
        }
        if (!in_array((string) ($data['status'] ?? 'active'), ['active', 'inactive'], true)) {
            $errors[] = $this->error('status', 'Location status is invalid.');
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
