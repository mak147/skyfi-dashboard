<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\DTOs;

final class ImportHotspotUsersData
{
    /** @param array<int, string> $usernames */
    public function __construct(
        public readonly int $routerId,
        public readonly array $usernames = [],
        public readonly ?int $defaultCustomerId = null,
        public readonly ?int $defaultConnectionId = null,
        public readonly ?int $defaultPackageId = null,
        public readonly bool $overwriteConflicts = false,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $usernames = [];
        if (isset($data['usernames']) && is_array($data['usernames'])) {
            foreach ($data['usernames'] as $u) {
                $trimmed = trim((string) $u);
                if ($trimmed !== '') {
                    $usernames[] = $trimmed;
                }
            }
        }

        return new self(
            routerId: (int) ($data['router_id'] ?? 0),
            usernames: $usernames,
            defaultCustomerId: isset($data['default_customer_id']) && is_numeric($data['default_customer_id']) && (int) $data['default_customer_id'] > 0 ? (int) $data['default_customer_id'] : null,
            defaultConnectionId: isset($data['default_connection_id']) && is_numeric($data['default_connection_id']) && (int) $data['default_connection_id'] > 0 ? (int) $data['default_connection_id'] : null,
            defaultPackageId: isset($data['default_package_id']) && is_numeric($data['default_package_id']) && (int) $data['default_package_id'] > 0 ? (int) $data['default_package_id'] : null,
            overwriteConflicts: (bool) ($data['overwrite_conflicts'] ?? false),
        );
    }
}
