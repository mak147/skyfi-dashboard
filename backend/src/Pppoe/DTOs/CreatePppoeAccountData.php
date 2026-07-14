<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\DTOs;

final class CreatePppoeAccountData
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly int $customerId,
        public readonly int $connectionId,
        public readonly int $packageId,
        public readonly int $routerId,
        public readonly string $profile,
        public readonly string $service = 'pppoe',
        public readonly ?string $ipPool = null,
        public readonly ?string $staticIp = null,
        public readonly ?string $macBinding = null,
        public readonly ?string $callerId = null,
        public readonly ?string $rateLimit = null,
        public readonly ?int $sessionTimeout = null,
        public readonly ?int $idleTimeout = null,
        public readonly int $sharedUsers = 1,
        public readonly string $status = 'active',
        public readonly ?string $notes = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            username: trim((string) ($data['username'] ?? '')),
            password: (string) ($data['password'] ?? ''),
            customerId: (int) ($data['customer_id'] ?? 0),
            connectionId: (int) ($data['connection_id'] ?? 0),
            packageId: (int) ($data['package_id'] ?? 0),
            routerId: (int) ($data['router_id'] ?? 0),
            profile: trim((string) ($data['profile'] ?? 'default')),
            service: trim((string) ($data['service'] ?? 'pppoe')),
            ipPool: isset($data['ip_pool']) && $data['ip_pool'] !== '' ? trim((string) $data['ip_pool']) : null,
            staticIp: isset($data['static_ip']) && $data['static_ip'] !== '' ? trim((string) $data['static_ip']) : null,
            macBinding: isset($data['mac_binding']) && $data['mac_binding'] !== '' ? trim((string) $data['mac_binding']) : null,
            callerId: isset($data['caller_id']) && $data['caller_id'] !== '' ? trim((string) $data['caller_id']) : null,
            rateLimit: isset($data['rate_limit']) && $data['rate_limit'] !== '' ? trim((string) $data['rate_limit']) : null,
            sessionTimeout: isset($data['session_timeout']) && is_numeric($data['session_timeout']) ? (int) $data['session_timeout'] : null,
            idleTimeout: isset($data['idle_timeout']) && is_numeric($data['idle_timeout']) ? (int) $data['idle_timeout'] : null,
            sharedUsers: isset($data['shared_users']) && is_numeric($data['shared_users']) ? (int) $data['shared_users'] : 1,
            status: isset($data['status']) && in_array($data['status'], ['active', 'disabled', 'suspended', 'pending'], true)
                ? (string) $data['status']
                : 'active',
            notes: isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        );
    }
}
