<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class UpdateHotspotProfileData
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?int $routerId = null,
        public readonly ?string $routerProfileName = null,
        public readonly ?string $rateLimitUp = null,
        public readonly ?string $rateLimitDown = null,
        public readonly ?int $sessionTimeout = null,
        public readonly ?int $idleTimeout = null,
        public readonly ?int $sharedUsers = null,
        public readonly ?string $macCookieTimeout = null,
        public readonly ?string $loginMethods = null,
        public readonly ?string $status = null,
        public readonly ?string $notes = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) ? trim((string) $data['name']) : null,
            routerId: isset($data['router_id']) && is_numeric($data['router_id']) ? (int) $data['router_id'] : null,
            routerProfileName: isset($data['router_profile_name']) ? trim((string) $data['router_profile_name']) : null,
            rateLimitUp: isset($data['rate_limit_up']) ? (trim((string) $data['rate_limit_up']) ?: null) : null,
            rateLimitDown: isset($data['rate_limit_down']) ? (trim((string) $data['rate_limit_down']) ?: null) : null,
            sessionTimeout: isset($data['session_timeout']) && is_numeric($data['session_timeout']) ? (int) $data['session_timeout'] : null,
            idleTimeout: isset($data['idle_timeout']) && is_numeric($data['idle_timeout']) ? (int) $data['idle_timeout'] : null,
            sharedUsers: isset($data['shared_users']) && is_numeric($data['shared_users']) ? max(1, (int) $data['shared_users']) : null,
            macCookieTimeout: isset($data['mac_cookie_timeout']) ? (trim((string) $data['mac_cookie_timeout']) ?: null) : null,
            loginMethods: isset($data['login_methods']) ? trim((string) $data['login_methods']) : null,
            status: isset($data['status']) && in_array($data['status'], ['active', 'inactive'], true) ? (string) $data['status'] : null,
            notes: isset($data['notes']) ? (trim((string) $data['notes']) ?: null) : null,
        );
    }
}
