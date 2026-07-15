<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Validators;

use SkyFi\Vendors\DTOs\VendorData;
use SkyFi\Shared\Exceptions\ValidationException;

final class VendorValidator
{
    public function validate(VendorData $data): void
    {
        $errors = [];
        if ($data->code === '') {
            $errors[] = $this->error('code', 'Supplier code is required.');
        }
        if ($data->name === '') {
            $errors[] = $this->error('name', 'Company name is required.');
        }
        if (!in_array($data->status, ['active', 'inactive', 'on_hold'], true)) {
            $errors[] = $this->error('status', 'Invalid status selected.');
        }
        if ($data->email !== null && $data->email !== '' && !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $this->error('email', 'Invalid email address format.');
        }
        if (strlen($data->currency) < 2 || strlen($data->currency) > 3) {
            $errors[] = $this->error('currency', 'Currency code must be 2-3 characters.');
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
