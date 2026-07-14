<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Validators;

use SkyFi\Hotspot\DTOs\CreateHotspotUserData;
use SkyFi\Hotspot\DTOs\UpdateHotspotUserData;
use SkyFi\Shared\Exceptions\ValidationException;

final class HotspotUserValidator
{
    public function validateCreate(CreateHotspotUserData $data): void
    {
        $errors = [];

        if ($data->username === '' || strlen($data->username) > 100) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Hotspot username is required and must not exceed 100 characters.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        } elseif (!preg_match('/^[A-Za-z0-9._\\-+@]+$/', $data->username)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Username contains invalid characters. Use letters, numbers, dots, hyphens, plus, or @.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        }

        if ($data->password === '' || strlen($data->password) < 4) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Hotspot password is required and must be at least 4 characters.',
                'source' => ['pointer' => '/data/attributes/password'],
            ];
        }

        if ($data->routerId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid MikroTik Router ID is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ];
        }

        if ($data->profileName === '' || strlen($data->profileName) > 100) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A hotspot profile name is required.',
                'source' => ['pointer' => '/data/attributes/profile_name'],
            ];
        }

        if ($data->macAddress !== null && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data->macAddress)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'MAC address must be formatted as XX:XX:XX:XX:XX:XX.',
                'source' => ['pointer' => '/data/attributes/mac_address'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateUpdate(UpdateHotspotUserData $data): void
    {
        $errors = [];

        if ($data->username !== null && ($data->username === '' || strlen($data->username) > 100)) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Hotspot username must not be empty or exceed 100 characters.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        } elseif ($data->username !== null && !preg_match('/^[A-Za-z0-9._\\-+@]+$/', $data->username)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Username contains invalid characters.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        }

        if ($data->password !== null && strlen($data->password) < 4) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Hotspot password must be at least 4 characters.',
                'source' => ['pointer' => '/data/attributes/password'],
            ];
        }

        if ($data->routerId !== null && $data->routerId <= 0) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'A valid Router ID is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ];
        }

        if ($data->profileName !== null && ($data->profileName === '' || strlen($data->profileName) > 100)) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'Hotspot profile name must not be empty.',
                'source' => ['pointer' => '/data/attributes/profile_name'],
            ];
        }

        if ($data->macAddress !== null && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data->macAddress)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'MAC address must be formatted as XX:XX:XX:XX:XX:XX.',
                'source' => ['pointer' => '/data/attributes/mac_address'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
