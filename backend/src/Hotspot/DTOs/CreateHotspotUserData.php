<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class CreateHotspotUserData
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
        public readonly int $routerId,
        public readonly string $profileName = 'default',
        public readonly ?int $profileId = null,
        public readonly ?int $customerId = null,
        public readonly ?int $connectionId = null,
        public readonly ?int $packageId = null,
        public readonly ?string $limitUptime = null,
        public readonly ?int $limitBytesIn = null,
        public readonly ?int $limitBytesOut = null,
        public readonly ?int $limitBytesTotal = null,
        public readonly ?string $macAddress = null,
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
            routerId: (int) ($data['router_id'] ?? 0),
            profileName: trim((string) ($data['profile_name'] ?? 'default')),
            profileId: isset($data['profile_id']) && is_numeric($data['profile_id']) ? (int) $data['profile_id'] : null,
            customerId: isset($data['customer_id']) && is_numeric($data['customer_id']) && (int) $data['customer_id'] > 0 ? (int) $data['customer_id'] : null,
            connectionId: isset($data['connection_id']) && is_numeric($data['connection_id']) && (int) $data['connection_id'] > 0 ? (int) $data['connection_id'] : null,
            packageId: isset($data['package_id']) && is_numeric($data['package_id']) && (int) $data['package_id'] > 0 ? (int) $data['package_id'] : null,
            limitUptime: isset($data['limit_uptime']) && $data['limit_uptime'] !== '' ? trim((string) $data['limit_uptime']) : null,
            limitBytesIn: isset($data['limit_bytes_in']) && is_numeric($data['limit_bytes_in']) ? (int) $data['limit_bytes_in'] : null,
            limitBytesOut: isset($data['limit_bytes_out']) && is_numeric($data['limit_bytes_out']) ? (int) $data['limit_bytes_out'] : null,
            limitBytesTotal: isset($data['limit_bytes_total']) && is_numeric($data['limit_bytes_total']) ? (int) $data['limit_bytes_total'] : null,
            macAddress: isset($data['mac_address']) && $data['mac_address'] !== '' ? trim((string) $data['mac_address']) : null,
            status: isset($data['status']) && in_array($data['status'], ['active', 'disabled', 'suspended', 'pending'], true) ? (string) $data['status'] : 'active',
            notes: isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        );
    }
}
