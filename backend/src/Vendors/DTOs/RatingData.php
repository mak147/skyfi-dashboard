<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class RatingData
{
    public function __construct(
        public readonly string $reviewPeriodStart,
        public readonly string $reviewPeriodEnd,
        public readonly float $productQualityScore,
        public readonly string $currency,
        public readonly ?string $notes,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $notes = isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null;
        return new self(
            trim((string) ($data['review_period_start'] ?? '')),
            trim((string) ($data['review_period_end'] ?? '')),
            (float) ($data['product_quality_score'] ?? 0),
            strtoupper(trim((string) ($data['currency'] ?? 'PKR'))),
            $notes,
        );
    }
}
