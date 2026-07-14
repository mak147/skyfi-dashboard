<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DomainModels;

final class VoucherBatch
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function batchCode(): string
    {
        return (string) $this->attributes['batch_code'];
    }

    public function hotspotProfileId(): int
    {
        return (int) $this->attributes['hotspot_profile_id'];
    }

    public function routerId(): int
    {
        return (int) $this->attributes['router_id'];
    }

    public function quantity(): int
    {
        return (int) $this->attributes['quantity'];
    }

    public function prefix(): ?string
    {
        return isset($this->attributes['prefix']) && $this->attributes['prefix'] !== ''
            ? (string) $this->attributes['prefix']
            : null;
    }

    public function pricePerVoucher(): ?float
    {
        return isset($this->attributes['price_per_voucher']) && $this->attributes['price_per_voucher'] !== null
            ? (float) $this->attributes['price_per_voucher']
            : null;
    }

    public function timeLimit(): ?string
    {
        return isset($this->attributes['time_limit']) && $this->attributes['time_limit'] !== ''
            ? (string) $this->attributes['time_limit']
            : null;
    }

    public function dataLimitMb(): ?int
    {
        return isset($this->attributes['data_limit_mb']) && $this->attributes['data_limit_mb'] !== null
            ? (int) $this->attributes['data_limit_mb']
            : null;
    }

    public function validityDays(): ?int
    {
        return isset($this->attributes['validity_days']) && $this->attributes['validity_days'] !== null
            ? (int) $this->attributes['validity_days']
            : null;
    }

    public function status(): string
    {
        return (string) ($this->attributes['status'] ?? 'active');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = $this->attributes;
        $data['id'] = $this->id();
        $data['hotspot_profile_id'] = $this->hotspotProfileId();
        $data['router_id'] = $this->routerId();
        $data['quantity'] = $this->quantity();
        return $data;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
