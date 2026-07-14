<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\DTOs;

final class SyncOptionsData
{
    public function __construct(
        public readonly ?int $routerId = null,
        public readonly bool $repairAccounts = false,
        public readonly bool $importOrphans = false,
        public readonly ?int $defaultCustomerId = null,
        public readonly ?int $defaultConnectionId = null,
        public readonly ?int $defaultPackageId = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            routerId: isset($data['router_id']) && is_numeric($data['router_id']) ? (int) $data['router_id'] : null,
            repairAccounts: filter_var($data['repair_accounts'] ?? false, FILTER_VALIDATE_BOOLEAN),
            importOrphans: filter_var($data['import_orphans'] ?? false, FILTER_VALIDATE_BOOLEAN),
            defaultCustomerId: isset($data['default_customer_id']) && is_numeric($data['default_customer_id']) ? (int) $data['default_customer_id'] : null,
            defaultConnectionId: isset($data['default_connection_id']) && is_numeric($data['default_connection_id']) ? (int) $data['default_connection_id'] : null,
            defaultPackageId: isset($data['default_package_id']) && is_numeric($data['default_package_id']) ? (int) $data['default_package_id'] : null,
        );
    }
}
