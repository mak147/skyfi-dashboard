<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DomainModels;

final class Voucher
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function code(): string
    {
        return (string) $this->attributes['code'];
    }

    public function batchId(): int
    {
        return (int) $this->attributes['batch_id'];
    }

    public function hotspotUserId(): ?int
    {
        return isset($this->attributes['hotspot_user_id']) && $this->attributes['hotspot_user_id'] !== null
            ? (int) $this->attributes['hotspot_user_id']
            : null;
    }

    public function status(): string
    {
        return (string) ($this->attributes['status'] ?? 'new');
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

    public function price(): ?float
    {
        return isset($this->attributes['price']) && $this->attributes['price'] !== null
            ? (float) $this->attributes['price']
            : null;
    }

    public function expiresAt(): ?string
    {
        return isset($this->attributes['expires_at']) && $this->attributes['expires_at'] !== null
            ? (string) $this->attributes['expires_at']
            : null;
    }

    public function usedAt(): ?string
    {
        return isset($this->attributes['used_at']) && $this->attributes['used_at'] !== null
            ? (string) $this->attributes['used_at']
            : null;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt() === null) {
            return false;
        }
        return strtotime($this->expiresAt()) < time();
    }

    public function isAvailable(): bool
    {
        return $this->status() === 'new' && !$this->isExpired();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = $this->attributes;
        $data['id'] = $this->id();
        $data['batch_id'] = $this->batchId();
        $data['is_expired'] = $this->isExpired();
        $data['is_available'] = $this->isAvailable();
        return $data;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
