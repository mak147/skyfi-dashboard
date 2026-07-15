<?php

declare(strict_types=1);

namespace SkyFi\Audit\Validators;

use SkyFi\Shared\Exceptions\ValidationException;

final class AuditValidator
{
    private const VALID_FORMATS = ['csv', 'json'];
    private const VALID_SEVERITIES = ['info', 'warning', 'critical'];
    private const VALID_POLICY_TYPES = ['data_retention', 'access_control', 'immutability', 'privacy', 'custom'];

    /** @param array<string, mixed> $data */
    public function validateExport(array $data): void
    {
        $errors = [];

        $format = $data['format'] ?? 'csv';
        if (!in_array($format, self::VALID_FORMATS, true)) {
            $errors[] = [
                'code' => 'invalid_format',
                'detail' => 'Format must be one of: csv, json.',
                'source' => ['pointer' => '/data/attributes/format'],
            ];
        }

        $severity = $data['severity'] ?? null;
        if ($severity !== null && !in_array($severity, self::VALID_SEVERITIES, true)) {
            $errors[] = [
                'code' => 'invalid_severity',
                'detail' => 'Severity must be one of: info, warning, critical.',
                'source' => ['pointer' => '/data/attributes/severity'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @param array<string, mixed> $data */
    public function validateCompliancePolicy(array $data): void
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Policy name is required.',
                'source' => ['pointer' => '/data/attributes/name'],
            ];
        }

        $policyType = $data['policy_type'] ?? 'custom';
        if (!in_array($policyType, self::VALID_POLICY_TYPES, true)) {
            $errors[] = [
                'code' => 'invalid_policy_type',
                'detail' => 'Policy type must be one of: data_retention, access_control, immutability, privacy, custom.',
                'source' => ['pointer' => '/data/attributes/policy_type'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @param array<string, mixed> $data */
    public function validateRetentionPolicy(array $data): void
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = [
                'code' => 'required',
                'detail' => 'Retention policy name is required.',
                'source' => ['pointer' => '/data/attributes/name'],
            ];
        }

        $retentionDays = (int) ($data['retention_days'] ?? 0);
        if ($retentionDays < 1) {
            $errors[] = [
                'code' => 'invalid_retention_days',
                'detail' => 'Retention days must be at least 1.',
                'source' => ['pointer' => '/data/attributes/retention_days'],
            ];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
