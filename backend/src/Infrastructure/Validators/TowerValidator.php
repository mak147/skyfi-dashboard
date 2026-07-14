<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Validators;

use SkyFi\Infrastructure\Data\CreateTowerData;
use SkyFi\Infrastructure\Data\UpdateTowerData;
use SkyFi\Shared\Exceptions\ValidationException;

final class TowerValidator
{
    /** @var array<string> */
    private const VALID_STATUSES = ['planning', 'active', 'maintenance', 'decommissioned'];

    /** @var array<string> */
    private const VALID_TOWER_TYPES = ['lattice', 'monopole', 'guyed', 'building', 'water_tank', 'other'];

    /** @var array<string> */
    private const VALID_OWNERS = ['owned', 'leased', 'shared', 'managed'];

    public function validateCreate(CreateTowerData $data): void
    {
        $errors = [];

        if (empty($data->name)) {
            $errors[] = ['code' => 'required', 'detail' => 'Name is required.', 'source' => ['pointer' => '/data/attributes/name']];
        } elseif (strlen($data->name) > 150) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Name must not exceed 150 characters.', 'source' => ['pointer' => '/data/attributes/name']];
        }

        if ($data->code !== null) {
            if (strlen($data->code) > 50) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Code must not exceed 50 characters.', 'source' => ['pointer' => '/data/attributes/code']];
            } elseif (!preg_match('/^[A-Z0-9_-]+$/', $data->code)) {
                $errors[] = ['code' => 'format', 'detail' => 'Code must contain only uppercase letters, numbers, underscores, and hyphens.', 'source' => ['pointer' => '/data/attributes/code']];
            }
        }

        if (!in_array($data->towerType, self::VALID_TOWER_TYPES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid tower type.', 'source' => ['pointer' => '/data/attributes/tower_type']];
        }

        if ($data->heightMeters !== null) {
            if (!preg_match('/^\d+(\.\d+)?$/', $data->heightMeters)) {
                $errors[] = ['code' => 'format', 'detail' => 'Height must be a valid positive number.', 'source' => ['pointer' => '/data/attributes/height_meters']];
            } else {
                $height = (float) $data->heightMeters;
                if ($height <= 0 || $height > 1000) {
                    $errors[] = ['code' => 'range', 'detail' => 'Height must be between 0 and 1000 meters.', 'source' => ['pointer' => '/data/attributes/height_meters']];
                }
            }
        }

        if (!in_array($data->owner, self::VALID_OWNERS, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid owner type.', 'source' => ['pointer' => '/data/attributes/owner']];
        }

        if ($data->addressLine1 !== null && strlen($data->addressLine1) > 500) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Address must not exceed 500 characters.', 'source' => ['pointer' => '/data/attributes/address_line1']];
        }

        if ($data->city !== null && strlen($data->city) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'City must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/city']];
        }

        if ($data->region !== null && strlen($data->region) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Region must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/region']];
        }

        $this->validateCoordinates($data->gpsLatitude, $data->gpsLongitude, $errors);

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

    public function validateUpdate(UpdateTowerData $data): void
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
            if (strlen($data->code) > 50) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Code must not exceed 50 characters.', 'source' => ['pointer' => '/data/attributes/code']];
            } elseif (!preg_match('/^[A-Z0-9_-]+$/', $data->code)) {
                $errors[] = ['code' => 'format', 'detail' => 'Code must contain only uppercase letters, numbers, underscores, and hyphens.', 'source' => ['pointer' => '/data/attributes/code']];
            }
        }

        if ($data->popSiteId !== null && $data->popSiteId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid POP site ID.', 'source' => ['pointer' => '/data/attributes/pop_site_id']];
        }

        if ($data->towerType !== null && !in_array($data->towerType, self::VALID_TOWER_TYPES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid tower type.', 'source' => ['pointer' => '/data/attributes/tower_type']];
        }

        if ($data->heightMeters !== null) {
            if (!preg_match('/^\d+(\.\d+)?$/', $data->heightMeters)) {
                $errors[] = ['code' => 'format', 'detail' => 'Height must be a valid positive number.', 'source' => ['pointer' => '/data/attributes/height_meters']];
            } else {
                $height = (float) $data->heightMeters;
                if ($height <= 0 || $height > 1000) {
                    $errors[] = ['code' => 'range', 'detail' => 'Height must be between 0 and 1000 meters.', 'source' => ['pointer' => '/data/attributes/height_meters']];
                }
            }
        }

        if ($data->owner !== null && !in_array($data->owner, self::VALID_OWNERS, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid owner type.', 'source' => ['pointer' => '/data/attributes/owner']];
        }

        if ($data->addressLine1 !== null && strlen($data->addressLine1) > 500) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Address must not exceed 500 characters.', 'source' => ['pointer' => '/data/attributes/address_line1']];
        }

        if ($data->city !== null && strlen($data->city) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'City must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/city']];
        }

        if ($data->region !== null && strlen($data->region) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Region must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/region']];
        }

        $this->validateCoordinates($data->gpsLatitude, $data->gpsLongitude, $errors);

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