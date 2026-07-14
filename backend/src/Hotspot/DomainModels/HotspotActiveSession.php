<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DomainModels;

final class HotspotActiveSession
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): string
    {
        return (string) ($this->attributes['.id'] ?? $this->attributes['id'] ?? uniqid('hs-'));
    }

    public function routerId(): int
    {
        return (int) ($this->attributes['router_id'] ?? 0);
    }

    public function routerName(): string
    {
        return (string) ($this->attributes['router_name'] ?? 'Router #' . $this->routerId());
    }

    public function username(): string
    {
        return (string) ($this->attributes['user'] ?? $this->attributes['username'] ?? '');
    }

    public function macAddress(): ?string
    {
        $mac = $this->attributes['mac-address'] ?? $this->attributes['mac_address'] ?? null;
        return $mac !== null && $mac !== '' ? (string) $mac : null;
    }

    public function ipAddress(): ?string
    {
        $ip = $this->attributes['address'] ?? $this->attributes['ip_address'] ?? null;
        return $ip !== null && $ip !== '' ? (string) $ip : null;
    }

    public function uptime(): string
    {
        return (string) ($this->attributes['uptime'] ?? '0s');
    }

    public function bytesIn(): int
    {
        return (int) ($this->attributes['bytes-in'] ?? $this->attributes['bytes_in'] ?? 0);
    }

    public function bytesOut(): int
    {
        return (int) ($this->attributes['bytes-out'] ?? $this->attributes['bytes_out'] ?? 0);
    }

    public function hotspotUserId(): ?int
    {
        return isset($this->attributes['hotspot_user_id']) && $this->attributes['hotspot_user_id'] !== null
            ? (int) $this->attributes['hotspot_user_id']
            : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'router_id' => $this->routerId(),
            'router_name' => $this->routerName(),
            'username' => $this->username(),
            'mac_address' => $this->macAddress(),
            'ip_address' => $this->ipAddress(),
            'uptime' => $this->uptime(),
            'bytes_in' => $this->bytesIn(),
            'bytes_out' => $this->bytesOut(),
            'hotspot_user_id' => $this->hotspotUserId(),
        ];
    }
}
