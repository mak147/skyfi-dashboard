<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Validators;

use SkyFi\Hotspot\DTOs\CreateHotspotProfileData;
use SkyFi\Hotspot\DTOs\UpdateHotspotProfileData;
use SkyFi\Shared\Exceptions\ValidationException;

final class HotspotProfileValidator
{
    public function validateCreate(CreateHotspotProfileData $data): void
    {
        $errors = [];

        if ($data->name === '' || strlen($data->name) > 100) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Profile name is required and must not exceed 100 characters.',
                'source' => ['pointer' => '/data/attributes/name'],
            ];
        }

        if ($data->routerId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid MikroTik Router ID is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ];
        }

        if ($data->routerProfileName === '' || strlen($data->routerProfileName) > 100) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Router profile name is required.',
                'source' => ['pointer' => '/data/attributes/router_profile_name'],
            ];
        }

        if ($data->sharedUsers < 1) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Shared users must be at least 1.',
                'source' => ['pointer' => '/data/attributes/shared_users'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateUpdate(UpdateHotspotProfileData $data): void
    {
        $errors = [];

        if ($data->name !== null && ($data->name === '' || strlen($data->name) > 100)) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Profile name must not be empty or exceed 100 characters.',
                'source' => ['pointer' => '/data/attributes/name'],
            ];
        }

        if ($data->routerId !== null && $data->routerId <= 0) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'A valid Router ID is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ];
        }

        if ($data->routerProfileName !== null && ($data->routerProfileName === '' || strlen($data->routerProfileName) > 100)) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Router profile name must not be empty.',
                'source' => ['pointer' => '/data/attributes/router_profile_name'],
            ];
        }

        if ($data->sharedUsers !== null && $data->sharedUsers < 1) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Shared users must be at least 1.',
                'source' => ['pointer' => '/data/attributes/shared_users'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
