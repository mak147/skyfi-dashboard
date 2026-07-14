<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DomainModels;

final class HotspotProfile
{
    /** @param array<string, mixed> $attributes */
    public function __construct(private readonly array $attributes)
    {
    }

    public function id(): int
    {
        return (int) $this->attributes['id'];
    }

    public function name(): string
    {
        return (string) $this->attributes['name'];
    }

    public function routerId(): int
    {
        return (int) $this->attributes['router_id'];
    }

    public function routerProfileName(): string
    {
        return (string) ($this->attributes['router_profile_name'] ?? 'default');
    }

    public function rateLimitUp(): ?string
    {
        return isset($this->attributes['rate_limit_up']) && $this->attributes['rate_limit_up'] !== ''
            ? (string) $this->attributes['rate_limit_up']
            : null;
    }

    public function rateLimitDown(): ?string
    {
        return isset($this->attributes['rate_limit_down']) && $this->attributes['rate_limit_down'] !== ''
            ? (string) $this->attributes['rate_limit_down']
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

    public function macCookieTimeout(): ?string
    {
        return isset($this->attributes['mac_cookie_timeout']) && $this->attributes['mac_cookie_timeout'] !== ''
            ? (string) $this->attributes['mac_cookie_timeout']
            : null;
    }

    public function loginMethods(): string
    {
        return (string) ($this->attributes['login_methods'] ?? 'http-pap');
    }

    public function status(): string
    {
        return (string) ($this->attributes['status'] ?? 'active');
    }

    public function syncStatus(): string
    {
        return (string) ($this->attributes['sync_status'] ?? 'out_of_sync');
    }

    public function isActive(): bool
    {
        return $this->status() === 'active';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = $this->attributes;
        $data['id'] = $this->id();
        $data['router_id'] = $this->routerId();
        $data['shared_users'] = $this->sharedUsers();
        return $data;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self($row);
    }
}
