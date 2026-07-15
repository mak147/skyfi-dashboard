<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Validators;

use SkyFi\Purchasing\DTOs\SupplierInvoiceData;
use SkyFi\Shared\Exceptions\ValidationException;

final class SupplierInvoiceValidator
{
    public function validate(SupplierInvoiceData $data): void
    {
        $errors = [];
        if ($data->invoiceNumber === '' || strlen($data->invoiceNumber) > 80) {
            $errors[] = $this->error('invoice_number', 'Invoice number is required and must be at most 80 characters.');
        }
        if ($data->vendorId < 1) {
            $errors[] = $this->error('vendor_id', 'A supplier is required.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->invoiceDate)) {
            $errors[] = $this->error('invoice_date', 'Invoice date must be in YYYY-MM-DD format.');
        }
        if ($data->dueDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data->dueDate)) {
            $errors[] = $this->error('due_date', 'Due date must be in YYYY-MM-DD format.');
        }
        if ($data->subtotal < 0) {
            $errors[] = $this->error('subtotal', 'Subtotal cannot be negative.');
        }
        if ($data->taxAmount < 0) {
            $errors[] = $this->error('tax_amount', 'Tax amount cannot be negative.');
        }
        if ($data->totalAmount < 0) {
            $errors[] = $this->error('total_amount', 'Total amount cannot be negative.');
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
