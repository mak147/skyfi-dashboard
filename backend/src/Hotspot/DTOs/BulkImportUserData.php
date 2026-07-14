<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class BulkImportUserData
{
    /** @param array<int, array<string, mixed>> $users */
    public function __construct(
        public readonly int $routerId,
        public readonly array $users,
        public readonly string $defaultProfileName = 'default',
        public readonly string $defaultStatus = 'active',
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $users = [];
        if (isset($data['users']) && is_array($data['users'])) {
            foreach ($data['users'] as $u) {
                if (is_array($u) && isset($u['username'])) {
                    $users[] = [
                        'username' => trim((string) $u['username']),
                        'password' => (string) ($u['password'] ?? ''),
                        'profile_name' => trim((string) ($u['profile_name'] ?? '')),
                        'limit_uptime' => isset($u['limit_uptime']) && $u['limit_uptime'] !== '' ? trim((string) $u['limit_uptime']) : null,
                        'limit_bytes_total' => isset($u['limit_bytes_total']) && is_numeric($u['limit_bytes_total']) ? (int) $u['limit_bytes_total'] : null,
                        'mac_address' => isset($u['mac_address']) && $u['mac_address'] !== '' ? trim((string) $u['mac_address']) : null,
                    ];
                }
            }
        }

        return new self(
            routerId: (int) ($data['router_id'] ?? 0),
            users: $users,
            defaultProfileName: trim((string) ($data['default_profile_name'] ?? 'default')),
            defaultStatus: isset($data['default_status']) && in_array($data['default_status'], ['active', 'disabled', 'pending'], true) ? (string) $data['default_status'] : 'active',
        );
    }
}
