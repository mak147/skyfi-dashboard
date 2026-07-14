<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\DomainModels;

final class PppoeSessionHistory
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function accountId(): int
    {
        return (int) $this->attributes['account_id'];
    }

    public function routerId(): int
    {
        return (int) $this->attributes['router_id'];
    }

    public function sessionId(): string
    {
        return (string) $this->attributes['session_id'];
    }

    public function username(): string
    {
        return (string) $this->attributes['username'];
    }

    public function ipAddress(): string
    {
        return (string) $this->attributes['ip_address'];
    }

    public function macAddress(): ?string
    {
        return isset($this->attributes['mac_address']) ? (string) $this->attributes['mac_address'] : null;
    }

    public function callerId(): ?string
    {
        return isset($this->attributes['caller_id']) ? (string) $this->attributes['caller_id'] : null;
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
        return (string) $this->attributes['started_at'];
    }

    public function endedAt(): string
    {
        return (string) $this->attributes['ended_at'];
    }

    public function disconnectReason(): ?string
    {
        return isset($this->attributes['disconnect_reason']) ? (string) $this->attributes['disconnect_reason'] : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'account_id' => $this->accountId(),
            'router_id' => $this->routerId(),
            'session_id' => $this->sessionId(),
            'username' => $this->username(),
            'ip_address' => $this->ipAddress(),
            'mac_address' => $this->macAddress(),
            'caller_id' => $this->callerId(),
            'uptime_seconds' => $this->uptimeSeconds(),
            'bytes_in' => $this->bytesIn(),
            'bytes_out' => $this->bytesOut(),
            'started_at' => $this->startedAt(),
            'ended_at' => $this->endedAt(),
            'disconnect_reason' => $this->disconnectReason(),
            'created_at' => $this->attributes['created_at'] ?? null,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
