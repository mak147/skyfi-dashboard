<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\DTOs;

final class UpdatePppoeAccountData
{
    public function __construct(
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?int $packageId = null,
        public readonly ?int $routerId = null,
        public readonly ?string $profile = null,
        public readonly ?string $service = null,
        public readonly ?string $ipPool = null,
        public readonly ?string $staticIp = null,
        public readonly ?string $macBinding = null,
        public readonly ?string $callerId = null,
        public readonly ?string $rateLimit = null,
        public readonly ?int $sessionTimeout = null,
        public readonly ?int $idleTimeout = null,
        public readonly ?int $sharedUsers = null,
        public readonly ?string $status = null,
        public readonly ?string $notes = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            username: array_key_exists('username', $data) && $data['username'] !== null ? trim((string) $data['username']) : null,
            password: array_key_exists('password', $data) && $data['password'] !== null && $data['password'] !== '' ? (string) $data['password'] : null,
            packageId: isset($data['package_id']) && is_numeric($data['package_id']) ? (int) $data['package_id'] : null,
            routerId: isset($data['router_id']) && is_numeric($data['router_id']) ? (int) $data['router_id'] : null,
            profile: array_key_exists('profile', $data) && $data['profile'] !== null ? trim((string) $data['profile']) : null,
            service: array_key_exists('service', $data) && $data['service'] !== null ? trim((string) $data['service']) : null,
            ipPool: array_key_exists('ip_pool', $data) ? ($data['ip_pool'] !== null && $data['ip_pool'] !== '' ? trim((string) $data['ip_pool']) : null) : null,
            staticIp: array_key_exists('static_ip', $data) ? ($data['static_ip'] !== null && $data['static_ip'] !== '' ? trim((string) $data['static_ip']) : null) : null,
            macBinding: array_key_exists('mac_binding', $data) ? ($data['mac_binding'] !== null && $data['mac_binding'] !== '' ? trim((string) $data['mac_binding']) : null) : null,
            callerId: array_key_exists('caller_id', $data) ? ($data['caller_id'] !== null && $data['caller_id'] !== '' ? trim((string) $data['caller_id']) : null) : null,
            rateLimit: array_key_exists('rate_limit', $data) ? ($data['rate_limit'] !== null && $data['rate_limit'] !== '' ? trim((string) $data['rate_limit']) : null) : null,
            sessionTimeout: array_key_exists('session_timeout', $data) ? (is_numeric($data['session_timeout']) ? (int) $data['session_timeout'] : null) : null,
            idleTimeout: array_key_exists('idle_timeout', $data) ? (is_numeric($data['idle_timeout']) ? (int) $data['idle_timeout'] : null) : null,
            sharedUsers: array_key_exists('shared_users', $data) && is_numeric($data['shared_users']) ? (int) $data['shared_users'] : null,
            status: array_key_exists('status', $data) && $data['status'] !== null ? (string) $data['status'] : null,
            notes: array_key_exists('notes', $data) ? ($data['notes'] !== null ? trim((string) $data['notes']) : null) : null,
        );
    }
}
