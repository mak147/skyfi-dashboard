<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Validators;

use SkyFi\Pppoe\DTOs\CreatePppoeAccountData;
use SkyFi\Pppoe\DTOs\UpdatePppoeAccountData;
use SkyFi\Shared\Exceptions\ValidationException;

final class PppoeValidator
{
    public function validateCreate(CreatePppoeAccountData $data): void
    {
        $errors = [];

        if ($data->username === '' || strlen($data->username) > 100) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'PPPoE username is required and must not exceed 100 characters.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        } elseif (!preg_match('/^[A-Za-z0-9._\-+@]+$/', $data->username)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Username contains invalid characters. Use letters, numbers, dots, hyphens, plus, or '@'.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        }

        if ($data->password === '' || strlen($data->password) < 6) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'PPPoE password is required and must be at least 6 characters.',
                'source' => ['pointer' => '/data/attributes/password'],
            ];
        }

        if ($data->customerId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid Customer ID is required.',
                'source' => ['pointer' => '/data/attributes/customer_id'],
            ];
        }

        if ($data->connectionId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid Connection ID is required.',
                'source' => ['pointer' => '/data/attributes/connection_id'],
            ];
        }

        if ($data->packageId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid Package ID is required.',
                'source' => ['pointer' => '/data/attributes/package_id'],
            ];
        }

        if ($data->routerId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A valid MikroTik Router ID is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ];
        }

        if ($data->profile === '' || strlen($data->profile) > 100) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'A PPPoE Profile name is required.',
                'source' => ['pointer' => '/data/attributes/profile'],
            ];
        }

        if ($data->staticIp !== null && !filter_var($data->staticIp, FILTER_VALIDATE_IP)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Static IP must be a valid IPv4 address.',
                'source' => ['pointer' => '/data/attributes/static_ip'],
            ];
        }

        if ($data->macBinding !== null && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data->macBinding)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'MAC binding must be formatted as XX:XX:XX:XX:XX:XX.',
                'source' => ['pointer' => '/data/attributes/mac_binding'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateUpdate(UpdatePppoeAccountData $data): void
    {
        $errors = [];

        if ($data->username !== null && ($data->username === '' || strlen($data->username) > 100)) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'PPPoE username must not be empty or exceed 100 characters.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        } elseif ($data->username !== null && !preg_match('/^[A-Za-z0-9._\-+@]+$/', $data->username)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Username contains invalid characters.',
                'source' => ['pointer' => '/data/attributes/username'],
            ];
        }

        if ($data->password !== null && strlen($data->password) < 6) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'PPPoE password must be at least 6 characters.',
                'source' => ['pointer' => '/data/attributes/password'],
            ];
        }

        if ($data->packageId !== null && $data->packageId <= 0) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'A valid Package ID is required.',
                'source' => ['pointer' => '/data/attributes/package_id'],
            ];
        }

        if ($data->routerId !== null && $data->routerId <= 0) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'A valid Router ID is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ];
        }

        if ($data->profile !== null && ($data->profile === '' || strlen($data->profile) > 100)) {
            $errors[] = [
                'code' => 'invalid_value',
                'detail' => 'PPPoE Profile name must not be empty.',
                'source' => ['pointer' => '/data/attributes/profile'],
            ];
        }

        if ($data->staticIp !== null && !filter_var($data->staticIp, FILTER_VALIDATE_IP)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Static IP must be a valid IPv4 address.',
                'source' => ['pointer' => '/data/attributes/static_ip'],
            ];
        }

        if ($data->macBinding !== null && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data->macBinding)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'MAC binding must be formatted as XX:XX:XX:XX:XX:XX.',
                'source' => ['pointer' => '/data/attributes/mac_binding'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
