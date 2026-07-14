<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Validators;

use SkyFi\Hotspot\DTOs\GenerateVoucherBatchData;
use SkyFi\Shared\Exceptions\ValidationException;

final class VoucherValidator
{
    public function validateGenerateBatch(GenerateVoucherBatchData $data): void
    {
        $errors = [];

        if ($data->hotspotProfileId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid hotspot profile is required.',
                'source' => ['pointer' => '/data/attributes/hotspot_profile_id'],
            ];
        }

        if ($data->routerId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid router is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ];
        }

        if ($data->quantity < 1 || $data->quantity > 1000) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Quantity must be between 1 and 1000.',
                'source' => ['pointer' => '/data/attributes/quantity'],
            ];
        }

        if ($data->prefix !== null && (!preg_match('/^[A-Z0-9]{0,10}$/', $data->prefix))) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Prefix must be uppercase alphanumeric, max 10 characters.',
                'source' => ['pointer' => '/data/attributes/prefix'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
