<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Validators;

use SkyFi\Vendors\DTOs\VendorContactData;
use SkyFi\Shared\Exceptions\ValidationException;

final class VendorContactValidator
{
    public function validate(VendorContactData $data): void
    {
        $errors = [];
        if ($data->vendorId < 1) {
            $errors[] = $this->error('vendor_id', 'A valid supplier ID is required.');
        }
        if ($data->firstName === '') {
            $errors[] = $this->error('first_name', 'First name is required.');
        }
        if ($data->lastName === '') {
            $errors[] = $this->error('last_name', 'Last name is required.');
        }
        if ($data->email !== null && $data->email !== '' && !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $this->error('email', 'Invalid email address format.');
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
