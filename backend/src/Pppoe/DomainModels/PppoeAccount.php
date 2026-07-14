<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\DomainModels;

final class PppoeAccount
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

    public function customerId(): int
    {
        return (int) $this->attributes['customer_id'];
    }

    public function connectionId(): int
    {
        return (int) $this->attributes['connection_id'];
    }

    public function packageId(): int
    {
        return (int) $this->attributes['package_id'];
    }

    public function routerId(): int
    {
        return (int) $this->attributes['router_id'];
    }

    public function profile(): string
    {
        return (string) $this->attributes['profile'];
    }

    public function service(): string
    {
        return (string) ($this->attributes['service'] ?? 'pppoe');
    }

    public function ipPool(): ?string
    {
        return isset($this->attributes['ip_pool']) && $this->attributes['ip_pool'] !== ''
            ? (string) $this->attributes['ip_pool']
            : null;
    }

    public function staticIp(): ?string
    {
        return isset($this->attributes['static_ip']) && $this->attributes['static_ip'] !== ''
            ? (string) $this->attributes['static_ip']
            : null;
    }

    public function macBinding(): ?string
    {
        return isset($this->attributes['mac_binding']) && $this->attributes['mac_binding'] !== ''
            ? (string) $this->attributes['mac_binding']
            : null;
    }

    public function callerId(): ?string
    {
        return isset($this->attributes['caller_id']) && $this->attributes['caller_id'] !== ''
            ? (string) $this->attributes['caller_id']
            : null;
    }

    public function rateLimit(): ?string
    {
        return isset($this->attributes['rate_limit']) && $this->attributes['rate_limit'] !== ''
            ? (string) $this->attributes['rate_limit']
            : null;
    }

    public function sessionTimeout(): ?int
    {
        return isset($this->attributes['session_timeout']) && $this->attributes['session_timeout'] !== null
            ? (int) $this->attributes['session_timeout']
            : null;
    }

    public function idleTimeout(): ?int
    {
        return isset($this->attributes['idle_timeout']) && $this->attributes['idle_timeout'] !== null
            ? (int) $this->attributes['idle_timeout']
            : null;
    }

    public function sharedUsers(): int
    {
        return isset($this->attributes['shared_users']) ? (int) $this->attributes['shared_users'] : 1;
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
        $data['customer_id'] = $this->customerId();
        $data['connection_id'] = $this->connectionId();
        $data['package_id'] = $this->packageId();
        $data['router_id'] = $this->routerId();
        $data['shared_users'] = $this->sharedUsers();
        $data['has_password'] = isset($this->attributes['password_encrypted']) && $this->attributes['password_encrypted'] !== '';
        return $data;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
