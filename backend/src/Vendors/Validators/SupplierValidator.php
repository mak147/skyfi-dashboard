<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Validators;

use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Vendors\DTOs\SupplierData;

final class SupplierValidator
{
    private const STATUSES = ['active', 'inactive', 'on_hold'];

    public function validate(SupplierData $data): void
    {
        $errors = [];
        if ($data->supplierCode === '' || strlen($data->supplierCode) > 50) $errors[] = $this->error('supplier_code', 'Supplier code is required and must be at most 50 characters.');
        if ($data->companyName === '' || strlen($data->companyName) > 200) $errors[] = $this->error('company_name', 'Company name is required and must be at most 200 characters.');
        if (!in_array($data->status, self::STATUSES, true)) $errors[] = $this->error('status', 'Status must be active, inactive, or on hold.');
        if (strlen($data->currency) !== 3 || preg_match('/^[A-Z]{3}$/', $data->currency) !== 1) $errors[] = $this->error('currency', 'Currency must be a three-letter code.');
        if ($data->email !== null && filter_var($data->email, FILTER_VALIDATE_EMAIL) === false) $errors[] = $this->error('email', 'Enter a valid email address.');
        if ($data->website !== null && filter_var($data->website, FILTER_VALIDATE_URL) === false) $errors[] = $this->error('website', 'Enter a valid website URL including its scheme.');
        if ($data->phone !== null && strlen($data->phone) > 50) $errors[] = $this->error('phone', 'Phone must be at most 50 characters.');
        if (strlen((string) $data->address) > 500) $errors[] = $this->error('address', 'Address must be at most 500 characters.');
        if ($errors !== []) throw new ValidationException($errors);
    }

    /** @param array<string, mixed> $data */
    public function validateCategory(array $data): void
    {
        $errors = [];
        if (trim((string) ($data['code'] ?? '')) === '' || strlen((string) $data['code']) > 50) $errors[] = $this->error('code', 'Category code is required and must be at most 50 characters.');
        if (trim((string) ($data['name'] ?? '')) === '' || strlen((string) $data['name']) > 150) $errors[] = $this->error('name', 'Category name is required and must be at most 150 characters.');
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'], true)) $errors[] = $this->error('status', 'Category status is invalid.');
        if ($errors !== []) throw new ValidationException($errors);
    }

    /** @return array<string, mixed> */ private function error(string $field, string $detail): array { return ['code' => 'validation_error', 'detail' => $detail, 'source' => ['pointer' => '/data/attributes/' . $field]]; }
}
