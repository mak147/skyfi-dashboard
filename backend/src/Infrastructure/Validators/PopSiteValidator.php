<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Validators;

use SkyFi\Infrastructure\Data\CreatePopSiteData;
use SkyFi\Infrastructure\Data\UpdatePopSiteData;
use SkyFi\Shared\Exceptions\ValidationException;

final class PopSiteValidator
{
    /** @var array<string> */
    private const VALID_STATUSES = ['planning', 'active', 'maintenance', 'decommissioned'];

    /** @var array<string> */
    private const VALID_POWER_STATUSES = ['grid', 'solar', 'generator', 'hybrid', 'unknown'];

    public function validateCreate(CreatePopSiteData $data): void
    {
        $errors = [];

        if (empty($data->name)) {
            $errors[] = ['code' => 'required', 'detail' => 'Name is required.', 'source' => ['pointer' => '/data/attributes/name']];
        } elseif (strlen($data->name) > 150) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Name must not exceed 150 characters.', 'source' => ['pointer' => '/data/attributes/name']];
        }

        if (empty($data->code)) {
            $errors[] = ['code' => 'required', 'detail' => 'Code is required.', 'source' => ['pointer' => '/data/attributes/code']];
        } elseif (strlen($data->code) > 50) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Code must not exceed 50 characters.', 'source' => ['pointer' => '/data/attributes/code']];
        } elseif (!preg_match('/^[A-Z0-9_-]+$/', $data->code)) {
            $errors[] = ['code' => 'format', 'detail' => 'Code must contain only uppercase letters, numbers, underscores, and hyphens.', 'source' => ['pointer' => '/data/attributes/code']];
        }

        if ($data->addressLine1 !== null && strlen($data->addressLine1) > 500) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Address line 1 must not exceed 500 characters.', 'source' => ['pointer' => '/data/attributes/address_line1']];
        }

        if ($data->city !== null && strlen($data->city) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'City must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/city']];
        }

        if ($data->region !== null && strlen($data->region) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Region must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/region']];
        }

        if ($data->country !== null && strlen($data->country) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Country must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/country']];
        }

        $this->validateCoordinates($data->gpsLatitude, $data->gpsLongitude, $errors);

        if ($data->contactPerson !== null && strlen($data->contactPerson) > 200) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Contact person must not exceed 200 characters.', 'source' => ['pointer' => '/data/attributes/contact_person']];
        }

        if ($data->contactPhone !== null && !preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $data->contactPhone)) {
            $errors[] = ['code' => 'format', 'detail' => 'Invalid phone number format.', 'source' => ['pointer' => '/data/attributes/contact_phone']];
        }

        if ($data->contactEmail !== null && !filter_var($data->contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['code' => 'format', 'detail' => 'Invalid email format.', 'source' => ['pointer' => '/data/attributes/contact_email']];
        }

        if (!in_array($data->powerStatus, self::VALID_POWER_STATUSES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid power status.', 'source' => ['pointer' => '/data/attributes/power_status']];
        }

        if ($data->fiberProvider !== null && strlen($data->fiberProvider) > 200) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Fiber provider must not exceed 200 characters.', 'source' => ['pointer' => '/data/attributes/fiber_provider']];
        }

        if (!in_array($data->status, self::VALID_STATUSES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid status.', 'source' => ['pointer' => '/data/attributes/status']];
        }

        if ($data->notes !== null && strlen($data->notes) > 5000) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Notes must not exceed 5000 characters.', 'source' => ['pointer' => '/data/attributes/notes']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateUpdate(UpdatePopSiteData $data): void
    {
        $errors = [];

        if ($data->name !== null) {
            if (empty($data->name)) {
                $errors[] = ['code' => 'required', 'detail' => 'Name cannot be empty.', 'source' => ['pointer' => '/data/attributes/name']];
            } elseif (strlen($data->name) > 150) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Name must not exceed 150 characters.', 'source' => ['pointer' => '/data/attributes/name']];
            }
        }

        if ($data->code !== null) {
            if (empty($data->code)) {
                $errors[] = ['code' => 'required', 'detail' => 'Code cannot be empty.', 'source' => ['pointer' => '/data/attributes/code']];
            } elseif (strlen($data->code) > 50) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Code must not exceed 50 characters.', 'source' => ['pointer' => '/data/attributes/code']];
            } elseif (!preg_match('/^[A-Z0-9_-]+$/', $data->code)) {
                $errors[] = ['code' => 'format', 'detail' => 'Code must contain only uppercase letters, numbers, underscores, and hyphens.', 'source' => ['pointer' => '/data/attributes/code']];
            }
        }

        if ($data->addressLine1 !== null && strlen($data->addressLine1) > 500) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Address line 1 must not exceed 500 characters.', 'source' => ['pointer' => '/data/attributes/address_line1']];
        }

        if ($data->city !== null && strlen($data->city) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'City must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/city']];
        }

        if ($data->region !== null && strlen($data->region) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Region must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/region']];
        }

        if ($data->country !== null && strlen($data->country) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Country must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/country']];
        }

        $this->validateCoordinates($data->gpsLatitude, $data->gpsLongitude, $errors);

        if ($data->contactPerson !== null && strlen($data->contactPerson) > 200) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Contact person must not exceed 200 characters.', 'source' => ['pointer' => '/data/attributes/contact_person']];
        }

        if ($data->contactPhone !== null && !preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $data->contactPhone)) {
            $errors[] = ['code' => 'format', 'detail' => 'Invalid phone number format.', 'source' => ['pointer' => '/data/attributes/contact_phone']];
        }

        if ($data->contactEmail !== null && !filter_var($data->contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['code' => 'format', 'detail' => 'Invalid email format.', 'source' => ['pointer' => '/data/attributes/contact_email']];
        }

        if ($data->powerStatus !== null && !in_array($data->powerStatus, self::VALID_POWER_STATUSES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid power status.', 'source' => ['pointer' => '/data/attributes/power_status']];
        }

        if ($data->fiberProvider !== null && strlen($data->fiberProvider) > 200) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Fiber provider must not exceed 200 characters.', 'source' => ['pointer' => '/data/attributes/fiber_provider']];
        }

        if ($data->status !== null && !in_array($data->status, self::VALID_STATUSES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid status.', 'source' => ['pointer' => '/data/attributes/status']];
        }

        if ($data->notes !== null && strlen($data->notes) > 5000) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Notes must not exceed 5000 characters.', 'source' => ['pointer' => '/data/attributes/notes']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    private function validateCoordinates(?string $lat, ?string $lon, array &$errors): void
    {
        if ($lat !== null) {
            if (!preg_match('/^-?\d+(\.\d+)?$/', $lat)) {
                $errors[] = ['code' => 'format', 'detail' => 'Latitude must be a valid decimal number.', 'source' => ['pointer' => '/data/attributes/gps_latitude']];
            } else {
                $latVal = (float) $lat;
                if ($latVal < -90 || $latVal > 90) {
                    $errors[] = ['code' => 'range', 'detail' => 'Latitude must be between -90 and 90.', 'source' => ['pointer' => '/data/attributes/gps_latitude']];
                }
            }
        }

        if ($lon !== null) {
            if (!preg_match('/^-?\d+(\.\d+)?$/', $lon)) {
                $errors[] = ['code' => 'format', 'detail' => 'Longitude must be a valid decimal number.', 'source' => ['pointer' => '/data/attributes/gps_longitude']];
            } else {
                $lonVal = (float) $lon;
                if ($lonVal < -180 || $lonVal > 180) {
                    $errors[] = ['code' => 'range', 'detail' => 'Longitude must be between -180 and 180.', 'source' => ['pointer' => '/data/attributes/gps_longitude']];
                }
            }
        }
    }
}