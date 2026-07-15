<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Validators;

use SkyFi\Vendors\DTOs\VendorQuotationData;
use SkyFi\Shared\Exceptions\ValidationException;

final class VendorQuotationValidator
{
    public function validate(VendorQuotationData $data): void
    {
        $errors = [];
        if ($data->vendorId < 1) {
            $errors[] = $this->error('vendor_id', 'A valid supplier ID is required.');
        }
        if ($data->quotationNumber === '') {
            $errors[] = $this->error('quotation_number', 'Quotation number is required.');
        }
        if ($data->quotationDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->quotationDate)) {
            $errors[] = $this->error('quotation_date', 'Quotation date must be in YYYY-MM-DD format.');
        }
        if ($data->validityDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->validityDate)) {
            $errors[] = $this->error('validity_date', 'Validity date must be in YYYY-MM-DD format.');
        }
        if ($data->totalAmount < 0) {
            $errors[] = $this->error('total_amount', 'Total amount cannot be negative.');
        }
        if (!in_array($data->status, ['received', 'under_review', 'accepted', 'rejected', 'expired'], true)) {
            $errors[] = $this->error('status', 'Invalid quotation status.');
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
