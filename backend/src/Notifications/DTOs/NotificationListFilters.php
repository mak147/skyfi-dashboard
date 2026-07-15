<?php

declare(strict_types=1);

namespace SkyFi\Notifications\DTOs;

final class NotificationListFilters
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $category = null,
        public readonly ?string $type = null,
        public readonly ?string $search = null,
        public readonly ?string $severity = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);

        return new self(
            status: isset($query['status']) && $query['status'] !== '' ? (string) $query['status'] : (isset($query['filter']['status']) ? (string) $query['filter']['status'] : null),
            category: isset($query['category']) && $query['category'] !== '' ? (string) $query['category'] : (isset($query['filter']['category']) ? (string) $query['filter']['category'] : null),
            type: isset($query['type']) && $query['type'] !== '' ? (string) $query['type'] : (isset($query['filter']['type']) ? (string) $query['filter']['type'] : null),
            search: isset($query['search']) && $query['search'] !== '' ? (string) $query['search'] : null,
            severity: isset($query['severity']) && $query['severity'] !== '' ? (string) $query['severity'] : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );
    }
}
