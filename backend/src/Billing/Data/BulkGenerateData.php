<?php

declare(strict_types=1);

namespace SkyFi\Billing\Data;

use SkyFi\Shared\Exceptions\ValidationException;

final class BulkGenerateData
{
    /**
     * @param array<int>|null $connectionIds
     */
    public function __construct(
        public readonly ?string $billingDate,
        public readonly ?array $connectionIds,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $errors = [];

        $billingDate = isset($data['billing_date']) && is_string($data['billing_date']) && $data['billing_date'] !== '' ? trim($data['billing_date']) : null;
        if ($billingDate !== null) {
            $d = \DateTime::createFromFormat('Y-m-d', $billingDate);
            if ($d === false || $d->format('Y-m-d') !== $billingDate) {
                $errors[] = ['code' => 'invalid_date', 'detail' => 'Billing date must be a valid date.', 'source' => ['pointer' => '/data/attributes/billing_date']];
            }
        }

        $connectionIds = null;
        if (isset($data['connection_ids']) && is_array($data['connection_ids'])) {
            $connectionIds = [];
            foreach ($data['connection_ids'] as $id) {
                if (is_numeric($id)) {
                    $connectionIds[] = (int) $id;
                }
            }
            if ($connectionIds === []) {
                $errors[] = ['code' => 'invalid', 'detail' => 'Connection IDs must contain valid integers.', 'source' => ['pointer' => '/data/attributes/connection_ids']];
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self(
            billingDate: $billingDate,
            connectionIds: $connectionIds,
        );
    }
}
