<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DomainModels;

final class HotspotUser
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function username(): string
    {
        return (string) $this->attributes['username'];
    }

    public function encryptedPassword(): string
    {
        return (string) $this->attributes['password_encrypted'];
    }

    public function customerId(): ?int
    {
        return isset($this->attributes['customer_id']) && $this->attributes['customer_id'] !== null
            ? (int) $this->attributes['customer_id']
            : null;
    }

    public function connectionId(): ?int
    {
        return isset($this->attributes['connection_id']) && $this->attributes['connection_id'] !== null
            ? (int) $this->attributes['connection_id']
            : null;
    }

    public function packageId(): ?int
    {
        return isset($this->attributes['package_id']) && $this->attributes['package_id'] !== null
            ? (int) $this->attributes['package_id']
            : null;
    }

    public function routerId(): int
    {
        return (int) $this->attributes['router_id'];
    }

    public function profileId(): ?int
    {
        return isset($this->attributes['profile_id']) && $this->attributes['profile_id'] !== null
            ? (int) $this->attributes['profile_id']
            : null;
    }

    public function profileName(): string
    {
        return (string) ($this->attributes['profile_name'] ?? 'default');
    }

    public function limitUptime(): ?string
    {
        return isset($this->attributes['limit_uptime']) && $this->attributes['limit_uptime'] !== ''
            ? (string) $this->attributes['limit_uptime']
            : null;
    }

    public function limitBytesIn(): ?int
    {
        return isset($this->attributes['limit_bytes_in']) && $this->attributes['limit_bytes_in'] !== null
            ? (int) $this->attributes['limit_bytes_in']
            : null;
    }

    public function limitBytesOut(): ?int
    {
        return isset($this->attributes['limit_bytes_out']) && $this->attributes['limit_bytes_out'] !== null
            ? (int) $this->attributes['limit_bytes_out']
            : null;
    }

    public function limitBytesTotal(): ?int
    {
        return isset($this->attributes['limit_bytes_total']) && $this->attributes['limit_bytes_total'] !== null
            ? (int) $this->attributes['limit_bytes_total']
            : null;
    }

    public function macAddress(): ?string
    {
        return isset($this->attributes['mac_address']) && $this->attributes['mac_address'] !== ''
            ? (string) $this->attributes['mac_address']
            : null;
    }

    public function status(): string
    {
        return (string) ($this->attributes['status'] ?? 'pending');
    }

    public function syncStatus(): string
    {
        return (string) ($this->attributes['sync_status'] ?? 'out_of_sync');
    }

    public function isEnabled(): bool
    {
        return $this->status() === 'active';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = $this->attributes;
        unset($data['password_encrypted']);
        $data['id'] = $this->id();
        $data['router_id'] = $this->routerId();
        $data['has_password'] = isset($this->attributes['password_encrypted']) && $this->attributes['password_encrypted'] !== '';
        return $data;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
