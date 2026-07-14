<?php

declare(strict_types=1);

namespace SkyFi\Billing\Data;

use SkyFi\Shared\Exceptions\ValidationException;

final class GenerateInvoiceData
{
    public function __construct(
        public readonly int $connectionId,
        public readonly ?string $billingPeriodStart,
        public readonly ?string $billingPeriodEnd,
        public readonly ?string $issueDate,
        public readonly ?string $dueDate,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $errors = [];

        $connectionId = isset($data['connection_id']) && is_numeric($data['connection_id']) ? (int) $data['connection_id'] : 0;
        if ($connectionId <= 0) {
            $errors[] = ['code' => 'required', 'detail' => 'Connection is required.', 'source' => ['pointer' => '/data/attributes/connection_id']];
        }

        $billingPeriodStart = isset($data['billing_period_start']) && is_string($data['billing_period_start']) && $data['billing_period_start'] !== '' ? trim($data['billing_period_start']) : null;
        $billingPeriodEnd = isset($data['billing_period_end']) && is_string($data['billing_period_end']) && $data['billing_period_end'] !== '' ? trim($data['billing_period_end']) : null;
        $issueDate = isset($data['issue_date']) && is_string($data['issue_date']) && $data['issue_date'] !== '' ? trim($data['issue_date']) : null;
        $dueDate = isset($data['due_date']) && is_string($data['due_date']) && $data['due_date'] !== '' ? trim($data['due_date']) : null;

        foreach (['billing_period_start' => $billingPeriodStart, 'billing_period_end' => $billingPeriodEnd, 'issue_date' => $issueDate, 'due_date' => $dueDate] as $key => $value) {
            if ($value !== null) {
                $d = \DateTime::createFromFormat('Y-m-d', $value);
                if ($d === false || $d->format('Y-m-d') !== $value) {
                    $errors[] = ['code' => 'invalid_date', 'detail' => "{$key} must be a valid date.", 'source' => ['pointer' => "/data/attributes/{$key}"]];
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self(
            connectionId: $connectionId,
            billingPeriodStart: $billingPeriodStart,
            billingPeriodEnd: $billingPeriodEnd,
            issueDate: $issueDate,
            dueDate: $dueDate,
            notes: isset($data['notes']) && is_string($data['notes']) && $data['notes'] !== '' ? trim($data['notes']) : null,
        );
    }
}
