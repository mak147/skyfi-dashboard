<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class UpdateHotspotUserData
{
    public function __construct(
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?int $routerId = null,
        public readonly ?string $profileName = null,
        public readonly ?int $profileId = null,
        public readonly ?int $customerId = null,
        public readonly ?int $connectionId = null,
        public readonly ?int $packageId = null,
        public readonly ?string $limitUptime = null,
        public readonly ?int $limitBytesIn = null,
        public readonly ?int $limitBytesOut = null,
        public readonly ?int $limitBytesTotal = null,
        public readonly ?string $macAddress = null,
        public readonly ?string $status = null,
        public readonly ?string $notes = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            username: isset($data['username']) ? trim((string) $data['username']) : null,
            password: isset($data['password']) && $data['password'] !== '' ? (string) $data['password'] : null,
            routerId: isset($data['router_id']) && is_numeric($data['router_id']) ? (int) $data['router_id'] : null,
            profileName: isset($data['profile_name']) ? trim((string) $data['profile_name']) : null,
            profileId: isset($data['profile_id']) && is_numeric($data['profile_id']) ? (int) $data['profile_id'] : null,
            customerId: isset($data['customer_id']) && is_numeric($data['customer_id']) ? (int) $data['customer_id'] : null,
            connectionId: isset($data['connection_id']) && is_numeric($data['connection_id']) ? (int) $data['connection_id'] : null,
            packageId: isset($data['package_id']) && is_numeric($data['package_id']) ? (int) $data['package_id'] : null,
            limitUptime: isset($data['limit_uptime']) ? (trim((string) $data['limit_uptime']) ?: null) : null,
            limitBytesIn: isset($data['limit_bytes_in']) && is_numeric($data['limit_bytes_in']) ? (int) $data['limit_bytes_in'] : null,
            limitBytesOut: isset($data['limit_bytes_out']) && is_numeric($data['limit_bytes_out']) ? (int) $data['limit_bytes_out'] : null,
            limitBytesTotal: isset($data['limit_bytes_total']) && is_numeric($data['limit_bytes_total']) ? (int) $data['limit_bytes_total'] : null,
            macAddress: isset($data['mac_address']) ? (trim((string) $data['mac_address']) ?: null) : null,
            status: isset($data['status']) && in_array($data['status'], ['active', 'disabled', 'suspended', 'pending', 'error'], true) ? (string) $data['status'] : null,
            notes: isset($data['notes']) ? (trim((string) $data['notes']) ?: null) : null,
        );
    }
}
