<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Validators;

use SkyFi\Infrastructure\Data\CreateNetworkDeviceData;
use SkyFi\Infrastructure\Data\UpdateNetworkDeviceData;
use SkyFi\Shared\Exceptions\ValidationException;

final class NetworkDeviceValidator
{
    /** @var array<string> */
    private const VALID_STATUSES = ['inventory', 'deployed', 'maintenance', 'offline', 'decommissioned'];

    /** @var array<string> */
    private const VALID_DEVICE_TYPES = ['router', 'switch', 'radio', 'access_point', 'olt', 'onu', 'ups', 'other'];

    public function validateCreate(CreateNetworkDeviceData $data): void
    {
        $errors = [];

        if (empty($data->name)) {
            $errors[] = ['code' => 'required', 'detail' => 'Name is required.', 'source' => ['pointer' => '/data/attributes/name']];
        } elseif (strlen($data->name) > 150) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Name must not exceed 150 characters.', 'source' => ['pointer' => '/data/attributes/name']];
        }

        if (!in_array($data->deviceType, self::VALID_DEVICE_TYPES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid device type.', 'source' => ['pointer' => '/data/attributes/device_type']];
        }

        if ($data->vendor !== null && strlen($data->vendor) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Vendor must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/vendor']];
        }

        if ($data->model !== null && strlen($data->model) > 150) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Model must not exceed 150 characters.', 'source' => ['pointer' => '/data/attributes/model']];
        }

        if ($data->serialNumber !== null) {
            if (strlen($data->serialNumber) > 100) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Serial number must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/serial_number']];
            }
        }

        if ($data->macAddress !== null) {
            if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data->macAddress)) {
                $errors[] = ['code' => 'format', 'detail' => 'Invalid MAC address format.', 'source' => ['pointer' => '/data/attributes/mac_address']];
            }
        }

        if ($data->ipAddress !== null) {
            if (!filter_var($data->ipAddress, FILTER_VALIDATE_IP)) {
                $errors[] = ['code' => 'format', 'detail' => 'Invalid IP address format.', 'source' => ['pointer' => '/data/attributes/ip_address']];
            }
        }

        if ($data->firmwareVersion !== null && strlen($data->firmwareVersion) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Firmware version must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/firmware_version']];
        }

        if ($data->locationDescription !== null && strlen($data->locationDescription) > 255) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Location description must not exceed 255 characters.', 'source' => ['pointer' => '/data/attributes/location_description']];
        }

        if ($data->managementVlan !== null && ($data->managementVlan < 1 || $data->managementVlan > 4094)) {
            $errors[] = ['code' => 'range', 'detail' => 'VLAN must be between 1 and 4094.', 'source' => ['pointer' => '/data/attributes/management_vlan']];
        }

        if ($data->managementUsername !== null && strlen($data->managementUsername) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Management username must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/management_username']];
        }

        if ($data->managementPassword !== null && strlen($data->managementPassword) > 255) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Management password must not exceed 255 characters.', 'source' => ['pointer' => '/data/attributes/management_password']];
        }

        if (!in_array($data->status, self::VALID_STATUSES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid status.', 'source' => ['pointer' => '/data/attributes/status']];
        }

        if ($data->notes !== null && strlen($data->notes) > 5000) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Notes must not exceed 5000 characters.', 'source' => ['pointer' => '/data/attributes/notes']];
        }

        if ($data->mikrotikRouterId !== null && $data->mikrotikRouterId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid MikroTik router ID.', 'source' => ['pointer' => '/data/attributes/mikrotik_router_id']];
        }

        if ($data->popSiteId !== null && $data->popSiteId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid POP site ID.', 'source' => ['pointer' => '/data/attributes/pop_site_id']];
        }

        if ($data->towerId !== null && $data->towerId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid tower ID.', 'source' => ['pointer' => '/data/attributes/tower_id']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateUpdate(UpdateNetworkDeviceData $data): void
    {
        $errors = [];

        if ($data->name !== null) {
            if (empty($data->name)) {
                $errors[] = ['code' => 'required', 'detail' => 'Name cannot be empty.', 'source' => ['pointer' => '/data/attributes/name']];
            } elseif (strlen($data->name) > 150) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Name must not exceed 150 characters.', 'source' => ['pointer' => '/data/attributes/name']];
            }
        }

        if ($data->deviceType !== null && !in_array($data->deviceType, self::VALID_DEVICE_TYPES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid device type.', 'source' => ['pointer' => '/data/attributes/device_type']];
        }

        if ($data->vendor !== null && strlen($data->vendor) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Vendor must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/vendor']];
        }

        if ($data->model !== null && strlen($data->model) > 150) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Model must not exceed 150 characters.', 'source' => ['pointer' => '/data/attributes/model']];
        }

        if ($data->serialNumber !== null) {
            if (strlen($data->serialNumber) > 100) {
                $errors[] = ['code' => 'max_length', 'detail' => 'Serial number must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/serial_number']];
            }
        }

        if ($data->macAddress !== null) {
            if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data->macAddress)) {
                $errors[] = ['code' => 'format', 'detail' => 'Invalid MAC address format.', 'source' => ['pointer' => '/data/attributes/mac_address']];
            }
        }

        if ($data->ipAddress !== null) {
            if (!filter_var($data->ipAddress, FILTER_VALIDATE_IP)) {
                $errors[] = ['code' => 'format', 'detail' => 'Invalid IP address format.', 'source' => ['pointer' => '/data/attributes/ip_address']];
            }
        }

        if ($data->firmwareVersion !== null && strlen($data->firmwareVersion) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Firmware version must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/firmware_version']];
        }

        if ($data->locationDescription !== null && strlen($data->locationDescription) > 255) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Location description must not exceed 255 characters.', 'source' => ['pointer' => '/data/attributes/location_description']];
        }

        if ($data->managementVlan !== null && ($data->managementVlan < 1 || $data->managementVlan > 4094)) {
            $errors[] = ['code' => 'range', 'detail' => 'VLAN must be between 1 and 4094.', 'source' => ['pointer' => '/data/attributes/management_vlan']];
        }

        if ($data->managementUsername !== null && strlen($data->managementUsername) > 100) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Management username must not exceed 100 characters.', 'source' => ['pointer' => '/data/attributes/management_username']];
        }

        if ($data->managementPassword !== null && strlen($data->managementPassword) > 255) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Management password must not exceed 255 characters.', 'source' => ['pointer' => '/data/attributes/management_password']];
        }

        if ($data->status !== null && !in_array($data->status, self::VALID_STATUSES, true)) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid status.', 'source' => ['pointer' => '/data/attributes/status']];
        }

        if ($data->notes !== null && strlen($data->notes) > 5000) {
            $errors[] = ['code' => 'max_length', 'detail' => 'Notes must not exceed 5000 characters.', 'source' => ['pointer' => '/data/attributes/notes']];
        }

        if ($data->mikrotikRouterId !== null && $data->mikrotikRouterId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid MikroTik router ID.', 'source' => ['pointer' => '/data/attributes/mikrotik_router_id']];
        }

        if ($data->popSiteId !== null && $data->popSiteId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid POP site ID.', 'source' => ['pointer' => '/data/attributes/pop_site_id']];
        }

        if ($data->towerId !== null && $data->towerId <= 0) {
            $errors[] = ['code' => 'invalid_value', 'detail' => 'Invalid tower ID.', 'source' => ['pointer' => '/data/attributes/tower_id']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}