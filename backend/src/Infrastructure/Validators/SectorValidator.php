<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Validators;

use SkyFi\Infrastructure\Data\CreateSectorData;
use SkyFi\Infrastructure\Data\UpdateSectorData;
use SkyFi\Shared\Exceptions\ValidationException;

final class SectorValidator
{
    /** @var array<string> */
    private const VALID_STATUSES = ['planning', 'active', 'maintenance', 'decommissioned'];

    public function validateCreate(CreateSectorData $data): void
    {
        $errors = [];

        if (empty($data->name)) {
            $errors[] = ['code' => 'required', 'detail' => 'Name is required.', 'source' => ['pointer' => '/data/attributes/name']];
        } elseif (strlen($data->name) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Name must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/name']];
        }

        if ($data->azimuth < 0 || $data->azimuth > 359) {
            $errors[] = ['code' => 'range', 'detail' => 'Azimuth must be between 0 and 359 degrees.', 'source' => ['pointer' => '/data/attributes/azimuth']];
        }

        if ($data->beamwidth !== null) {
            if ($data->beamwidth < 1 || $data->beamwidth > 360) {
                $errors[] = ['code' => 'range', 'detail' => 'Beamwidth must be between 1 and 360 degrees.', 'source' => ['pointer' => '/data/attributes/beamwidth']];
            }
        }

        if ($data->frequencyMhz <= 0) {
            $errors[] = ['code' => 'required', 'detail' => 'Frequency is required and must be positive.', 'source' => ['pointer' => '/data/attributes/frequency_mhz']];
        }

        if ($data->channelWidthMhz !== null && $data->channelWidthMhz <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Channel width must be positive.', 'source' => ['pointer' => '/data/attributes/channel_width_mhz']];
        }

        if ($data->ssid !== null && strlen($data->ssid) > 64) {
            $errors[] = ['code' => 'max_length', 'detail' => 'SSID must not exceed 64 characters.', 'source' => ['pointer' => '/data/attributes/ssid']];
        }

        if ($data->eirpDbm !== null && ($data->eirpDbm < -100 || $data->eirpDbm > 100)) {
            $errors[] = ['code' => 'range', 'detail' => 'EIRP must be between -100 and 100 dBm.', 'source' => ['pointer' => '/data/attributes/eirp_dbm']];
        }

        if ($data->deviceId !== null && $data->deviceId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid device ID.', 'source' => ['pointer' => '/data/attributes/device_id']];
        }

        if ($data->capacityMbps !== null && $data->capacityMbps <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Capacity must be positive.', 'source' => ['pointer' => '/data/attributes/capacity_mbps']];
        }

        if ($data->maxSubscribers !== null && $data->maxSubscribers <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Max subscribers must be positive.', 'source' => ['pointer' => '/data/attributes/max_subscribers']];
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

    public function validateUpdate(UpdateSectorData $data): void
    {
        $errors = [];

        if ($data->name !== null) {
            if (empty($data->name)) {
                $errors[] = ['code' => 'required', 'detail' => 'Name cannot be empty.', 'source' => ['pointer' => '/data/attributes/name']];
            } elseif (strlen($data->name) > 100) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Name must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/name']];
            }
        }

        if ($data->towerId !== null && $data->towerId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid tower ID.', 'source' => ['pointer' => '/data/attributes/tower_id']];
        }

        if ($data->azimuth !== null && ($data->azimuth < 0 || $data->azimuth > 359)) {
            $errors[] = ['code' => 'range', 'detail' => 'Azimuth must be between 0 and 359 degrees.', 'source' => ['pointer' => '/data/attributes/azimuth']];
        }

        if ($data->beamwidth !== null && ($data->beamwidth < 1 || $data->beamwidth > 360)) {
            $errors[] = ['code' => 'range', 'detail' => 'Beamwidth must be between 1 and 360 degrees.', 'source' => ['pointer' => '/data/attributes/beamwidth']];
        }

        if ($data->frequencyMhz !== null && $data->frequencyMhz <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Frequency must be positive.', 'source' => ['pointer' => '/data/attributes/frequency_mhz']];
        }

        if ($data->channelWidthMhz !== null && $data->channelWidthMhz <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Channel width must be positive.', 'source' => ['pointer' => '/data/attributes/channel_width_mhz']];
        }

        if ($data->ssid !== null && strlen($data->ssid) > 64) {
            $errors[] = ['code' => 'max_length', 'detail' => 'SSID must not exceed 64 characters.', 'source' => ['pointer' => '/data/attributes/ssid']];
        }

        if ($data->eirpDbm !== null && ($data->eirpDbm < -100 || $data->eirpDbm > 100)) {
            $errors[] = ['code' => 'range', 'detail' => 'EIRP must be between -100 and 100 dBm.', 'source' => ['pointer' => '/data/attributes/eirp_dbm']];
        }

        if ($data->deviceId !== null && $data->deviceId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid device ID.', 'source' => ['pointer' => '/data/attributes/device_id']];
        }

        if ($data->capacityMbps !== null && $data->capacityMbps <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Capacity must be positive.', 'source' => ['pointer' => '/data/attributes/capacity_mbps']];
        }

        if ($data->maxSubscribers !== null && $data->maxSubscribers <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Max subscribers must be positive.', 'source' => ['pointer' => '/data/attributes/max_subscribers']];
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
}