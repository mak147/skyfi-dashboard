<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Validators;

use SkyFi\Vendors\DTOs\VendorContractData;
use SkyFi\Shared\Exceptions\ValidationException;

final class VendorContractValidator
{
    public function validate(VendorContractData $data): void
    {
        $errors = [];
        if ($data->vendorId < 1) {
            $errors[] = $this->error('vendor_id', 'A valid supplier ID is required.');
        }
        if ($data->contractNumber === '') {
            $errors[] = $this->error('contract_number', 'Contract number is required.');
        }
        if ($data->title === '') {
            $errors[] = $this->error('title', 'Contract title is required.');
        }
        if ($data->startDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->startDate)) {
            $errors[] = $this->error('start_date', 'Start date must be in YYYY-MM-DD format.');
        }
        if ($data->endDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->endDate)) {
            $errors[] = $this->error('end_date', 'End date must be in YYYY-MM-DD format.');
        }
        if ($data->renewalDate !== null && $data->renewalDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->renewalDate)) {
            $errors[] = $this->error('renewal_date', 'Renewal date must be in YYYY-MM-DD format.');
        }
        if ($data->contractValue < 0) {
            $errors[] = $this->error('contract_value', 'Contract value cannot be negative.');
        }
        if (!in_array($data->status, ['draft', 'active', 'expiring', 'expired', 'terminated'], true)) {
            $errors[] = $this->error('status', 'Invalid contract status.');
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
