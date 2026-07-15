<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Validators;

use SkyFi\Vendors\DTOs\VendorRatingData;
use SkyFi\Shared\Exceptions\ValidationException;

final class VendorRatingValidator
{
    public function validate(VendorRatingData $data): void
    {
        $errors = [];
        if ($data->vendorId < 1) {
            $errors[] = $this->error('vendor_id', 'A valid supplier ID is required.');
        }
        if ($data->deliveryPerformance < 0 || $data->deliveryPerformance > 100) {
            $errors[] = $this->error('delivery_performance', 'Delivery performance must be between 0 and 100.');
        }
        if ($data->orderCompletion < 0 || $data->orderCompletion > 100) {
            $errors[] = $this->error('order_completion', 'Order completion must be between 0 and 100.');
        }
        if ($data->productQuality < 0 || $data->productQuality > 100) {
            $errors[] = $this->error('product_quality', 'Product quality must be between 0 and 100.');
        }
        if ($data->returnRate < 0 || $data->returnRate > 100) {
            $errors[] = $this->error('return_rate', 'Return rate must be between 0 and 100.');
        }
        if ($data->averageLeadTimeDays < 0) {
            $errors[] = $this->error('average_lead_time_days', 'Average lead time cannot be negative.');
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
