<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class CreateHotspotProfileData
{
    public function __construct(
        public readonly string $name,
        public readonly int $routerId,
        public readonly string $routerProfileName,
        public readonly ?string $rateLimitUp = null,
        public readonly ?string $rateLimitDown = null,
        public readonly ?int $sessionTimeout = null,
        public readonly ?int $idleTimeout = null,
        public readonly int $sharedUsers = 1,
        public readonly ?string $macCookieTimeout = null,
        public readonly string $loginMethods = 'http-pap',
        public readonly string $status = 'active',
        public readonly ?string $notes = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) ($data['name'] ?? '')),
            routerId: (int) ($data['router_id'] ?? 0),
            routerProfileName: trim((string) ($data['router_profile_name'] ?? 'default')),
            rateLimitUp: isset($data['rate_limit_up']) && $data['rate_limit_up'] !== '' ? trim((string) $data['rate_limit_up']) : null,
            rateLimitDown: isset($data['rate_limit_down']) && $data['rate_limit_down'] !== '' ? trim((string) $data['rate_limit_down']) : null,
            sessionTimeout: isset($data['session_timeout']) && is_numeric($data['session_timeout']) ? (int) $data['session_timeout'] : null,
            idleTimeout: isset($data['idle_timeout']) && is_numeric($data['idle_timeout']) ? (int) $data['idle_timeout'] : null,
            sharedUsers: isset($data['shared_users']) && is_numeric($data['shared_users']) ? max(1, (int) $data['shared_users']) : 1,
            macCookieTimeout: isset($data['mac_cookie_timeout']) && $data['mac_cookie_timeout'] !== '' ? trim((string) $data['mac_cookie_timeout']) : null,
            loginMethods: trim((string) ($data['login_methods'] ?? 'http-pap')),
            status: isset($data['status']) && in_array($data['status'], ['active', 'inactive'], true) ? (string) $data['status'] : 'active',
            notes: isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        );
    }
}
