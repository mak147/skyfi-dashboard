<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Validators;

use SkyFi\Monitoring\DTOs\DeviceHealthCheckRequest;
use SkyFi\Monitoring\DTOs\LogMonitoringEventData;
use SkyFi\Shared\Exceptions\ValidationException;

final class MonitoringValidator
{
    /** @var array<string> */
    private const VALID_EVENT_TYPES = [
        'device_status_change', 'interface_status_change', 'health_check',
        'sync_event', 'alert_triggered', 'threshold_violation',
    ];

    /** @var array<string> */
    private const VALID_SEVERITIES = ['info', 'warning', 'critical'];

    /** @var array<string> */
    private const VALID_DEVICE_TYPES = ['mikrotik_router', 'network_device'];

    public function validateHealthCheckRequest(DeviceHealthCheckRequest $request): void
    {
        $errors = [];

        if (!in_array($request->deviceType, self::VALID_DEVICE_TYPES, true)) {
            $errors[] = [
                'code' => 'invalid_device_type',
                'detail' => 'Device type must be one of: ' . implode(', ', self::VALID_DEVICE_TYPES),
                'source' => ['pointer' => '/data/attributes/device_type'],
            ];
        }

        if ($request->deviceId <= 0) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Device ID must be a positive integer.',
                'source' => ['pointer' => '/data/attributes/device_id'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateEventLog(LogMonitoringEventData $data): void
    {
        $errors = [];

        if (!in_array($data->eventType, self::VALID_EVENT_TYPES, true)) {
            $errors[] = [
                'code' => 'invalid_event_type',
                'detail' => 'Event type must be one of: ' . implode(', ', self::VALID_EVENT_TYPES),
                'source' => ['pointer' => '/data/attributes/event_type'],
            ];
        }

        if (!in_array($data->severity, self::VALID_SEVERITIES, true)) {
            $errors[] = [
                'code' => 'invalid_severity',
                'detail' => 'Severity must be one of: ' . implode(', ', self::VALID_SEVERITIES),
                'source' => ['pointer' => '/data/attributes/severity'],
            ];
        }

        if (trim($data->message) === '') {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Message is required for event log.',
                'source' => ['pointer' => '/data/attributes/message'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
