<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Validators;

use SkyFi\Monitoring\DTOs\CreateAlertData;
use SkyFi\Monitoring\DTOs\UpdateAlertStatusData;
use SkyFi\Shared\Exceptions\ValidationException;

final class AlertValidator
{
    /** @var array<string> */
    private const VALID_TYPES = [
        'device_offline', 'high_cpu', 'high_memory',
        'interface_down', 'link_degradation', 'auth_failure', 'sync_failure',
    ];

    /** @var array<string> */
    private const VALID_SEVERITIES = ['info', 'warning', 'critical'];

    /** @var array<string> */
    private const VALID_STATUSES = ['new', 'acknowledged', 'resolved', 'dismissed'];

    public function validateCreate(CreateAlertData $data): void
    {
        $errors = [];

        if (!in_array($data->alertType, self::VALID_TYPES, true)) {
            $errors[] = [
                'code' => 'invalid_alert_type',
                'detail' => 'Alert type must be one of: ' . implode(', ', self::VALID_TYPES),
                'source' => ['pointer' => '/data/attributes/alert_type'],
            ];
        }

        if (!in_array($data->severity, self::VALID_SEVERITIES, true)) {
            $errors[] = [
                'code' => 'invalid_severity',
                'detail' => 'Severity must be one of: ' . implode(', ', self::VALID_SEVERITIES),
                'source' => ['pointer' => '/data/attributes/severity'],
            ];
        }

        if (trim($data->title) === '') {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Alert title is required.',
                'source' => ['pointer' => '/data/attributes/title'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateStatusUpdate(UpdateAlertStatusData $data): void
    {
        $errors = [];

        if (!in_array($data->status, self::VALID_STATUSES, true)) {
            $errors[] = [
                'code' => 'invalid_status',
                'detail' => 'Status must be one of: ' . implode(', ', self::VALID_STATUSES),
                'source' => ['pointer' => '/data/attributes/status'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
