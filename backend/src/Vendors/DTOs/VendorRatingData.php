<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class VendorRatingData
{
    public function __construct(
        public readonly int $vendorId,
        public readonly string $evaluationDate,
        public readonly float $deliveryPerformance,
        public readonly float $orderCompletion,
        public readonly float $productQuality,
        public readonly float $returnRate,
        public readonly int $averageLeadTimeDays,
        public readonly ?string $comments,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            vendorId: (int) ($data['vendor_id'] ?? 0),
            evaluationDate: trim((string) ($data['evaluation_date'] ?? date('Y-m-d'))),
            deliveryPerformance: (float) ($data['delivery_performance'] ?? 100.0),
            orderCompletion: (float) ($data['order_completion'] ?? 100.0),
            productQuality: (float) ($data['product_quality'] ?? 100.0),
            returnRate: (float) ($data['return_rate'] ?? 0.0),
            averageLeadTimeDays: max(0, (int) ($data['average_lead_time_days'] ?? 7)),
            comments: isset($data['comments']) && is_string($data['comments']) ? trim($data['comments']) : null,
        );
    }
}
