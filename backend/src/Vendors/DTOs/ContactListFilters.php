<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class ContactListFilters
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $search = null,
        public readonly ?int $vendorId = null,
        public readonly ?string $department = null,
        public readonly ?bool $isPrimary = null,
        public readonly ?bool $isEmergency = null,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query, ?int $vendorId = null): self
    {
        $filters = is_array($query['filter'] ?? null) ? $query['filter'] : $query;
        $page = is_array($query['page'] ?? null) ? $query['page'] : [];
        $bool = static fn(string $key): ?bool => array_key_exists($key, $filters) && $filters[$key] !== '' ? filter_var($filters[$key], FILTER_VALIDATE_BOOLEAN) : null;
        return new self(
            max(1, (int) ($page['number'] ?? $query['page'] ?? 1)),
            min(100, max(1, (int) ($page['size'] ?? $query['per_page'] ?? 25))),
            trim((string) ($filters['search'] ?? '')) ?: null,
            $vendorId ?? (isset($filters['vendor_id']) && $filters['vendor_id'] !== '' ? (int) $filters['vendor_id'] : null),
            trim((string) ($filters['department'] ?? '')) ?: null,
            $bool('is_primary'),
            $bool('is_emergency'),
        );
    }
}
