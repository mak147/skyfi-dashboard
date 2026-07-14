<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DomainModels;

final class HotspotSessionHistory
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function hotspotUserId(): ?int
    {
        return isset($this->attributes['hotspot_user_id']) && $this->attributes['hotspot_user_id'] !== null
            ? (int) $this->attributes['hotspot_user_id']
            : null;
    }

    public function routerId(): int
    {
        return (int) $this->attributes['router_id'];
    }

    public function username(): string
    {
        return (string) ($this->attributes['username'] ?? '');
    }

    public function macAddress(): ?string
    {
        return isset($this->attributes['mac_address']) && $this->attributes['mac_address'] !== ''
            ? (string) $this->attributes['mac_address']
            : null;
    }

    public function ipAddress(): ?string
    {
        return isset($this->attributes['ip_address']) && $this->attributes['ip_address'] !== ''
            ? (string) $this->attributes['ip_address']
            : null;
    }

    public function uptimeSeconds(): int
    {
        return (int) ($this->attributes['uptime_seconds'] ?? 0);
    }

    public function bytesIn(): int
    {
        return (int) ($this->attributes['bytes_in'] ?? 0);
    }

    public function bytesOut(): int
    {
        return (int) ($this->attributes['bytes_out'] ?? 0);
    }

    public function startedAt(): string
    {
        return (string) ($this->attributes['started_at'] ?? '');
    }

    public function endedAt(): ?string
    {
        return isset($this->attributes['ended_at']) && $this->attributes['ended_at'] !== null
            ? (string) $this->attributes['ended_at']
            : null;
    }

    public function disconnectReason(): ?string
    {
        return isset($this->attributes['disconnect_reason']) && $this->attributes['disconnect_reason'] !== ''
            ? (string) $this->attributes['disconnect_reason']
            : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = $this->attributes;
        $data['id'] = $this->id();
        $data['router_id'] = $this->routerId();
        return $data;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
