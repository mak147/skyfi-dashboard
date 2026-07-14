<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class GenerateVoucherBatchData
{
    public function __construct(
        public readonly int $hotspotProfileId,
        public readonly int $routerId,
        public readonly int $quantity,
        public readonly ?string $prefix = null,
        public readonly ?float $pricePerVoucher = null,
        public readonly ?string $timeLimit = null,
        public readonly ?int $dataLimitMb = null,
        public readonly ?int $validityDays = null,
        public readonly ?string $notes = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            hotspotProfileId: (int) ($data['hotspot_profile_id'] ?? 0),
            routerId: (int) ($data['router_id'] ?? 0),
            quantity: max(1, min(1000, (int) ($data['quantity'] ?? 1))),
            prefix: isset($data['prefix']) && $data['prefix'] !== '' ? strtoupper(trim((string) $data['prefix'])) : null,
            pricePerVoucher: isset($data['price_per_voucher']) && is_numeric($data['price_per_voucher']) ? (float) $data['price_per_voucher'] : null,
            timeLimit: isset($data['time_limit']) && $data['time_limit'] !== '' ? trim((string) $data['time_limit']) : null,
            dataLimitMb: isset($data['data_limit_mb']) && is_numeric($data['data_limit_mb']) ? (int) $data['data_limit_mb'] : null,
            validityDays: isset($data['validity_days']) && is_numeric($data['validity_days']) ? (int) $data['validity_days'] : null,
            notes: isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        );
    }
}
