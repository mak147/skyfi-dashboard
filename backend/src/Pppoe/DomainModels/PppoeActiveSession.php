<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\DomainModels;

final class PppoeActiveSession
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): string
    {
        return (string) ($this->attributes['id'] ?? $this->attributes['.id'] ?? '');
    }

    public function routerId(): int
    {
        return (int) ($this->attributes['router_id'] ?? 0);
    }

    public function username(): string
    {
        return (string) ($this->attributes['name'] ?? $this->attributes['username'] ?? '');
    }

    public function service(): string
    {
        return (string) ($this->attributes['service'] ?? 'pppoe');
    }

    public function callerId(): ?string
    {
        return isset($this->attributes['caller-id']) && $this->attributes['caller-id'] !== ''
            ? (string) $this->attributes['caller-id']
            : (isset($this->attributes['caller_id']) && $this->attributes['caller_id'] !== '' ? (string) $this->attributes['caller_id'] : null);
    }

    public function ipAddress(): ?string
    {
        return isset($this->attributes['address']) && $this->attributes['address'] !== ''
            ? (string) $this->attributes['address']
            : (isset($this->attributes['ip_address']) ? (string) $this->attributes['ip_address'] : null);
    }

    public function uptime(): string
    {
        return (string) ($this->attributes['uptime'] ?? '0s');
    }

    public function encoding(): ?string
    {
        return isset($this->attributes['encoding']) ? (string) $this->attributes['encoding'] : null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'router_id' => $this->routerId(),
            'username' => $this->username(),
            'service' => $this->service(),
            'caller_id' => $this->callerId(),
            'ip_address' => $this->ipAddress(),
            'uptime' => $this->uptime(),
            'encoding' => $this->encoding(),
            'router_name' => $this->attributes['router_name'] ?? null,
            'customer_id' => $this->attributes['customer_id'] ?? null,
            'customer_name' => $this->attributes['customer_name'] ?? null,
            'package_name' => $this->attributes['package_name'] ?? null,
            'account_id' => $this->attributes['account_id'] ?? null,
        ];
    }
}
