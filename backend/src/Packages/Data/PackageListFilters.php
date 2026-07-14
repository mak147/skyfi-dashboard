<?php

declare(strict_types=1);

namespace SkyFi\Packages\Data;

final class PackageListFilters
{
    private const SORTS = ['name', 'code', 'status', 'category', 'monthly_price', 'download_speed', 'created_at', 'updated_at'];

    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly ?string $category,
        public readonly ?string $billingCycle,
        public readonly ?bool $unlimited,
        public readonly string $sort,
        public readonly bool $descending,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = max(1, (int) ($query['page']['number'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['page']['size'] ?? 15)));
        $filter = is_array($query['filter'] ?? null) ? $query['filter'] : [];
        $rawSort = is_string($query['sort'] ?? null) ? $query['sort'] : '-created_at';
        $descending = str_starts_with($rawSort, '-');
        $sort = ltrim($rawSort, '-');
        if (!in_array($sort, self::SORTS, true)) {
            $sort = 'created_at';
            $descending = true;
        }
        $unlimited = null;
        if (isset($filter['unlimited'])) {
            $unlimited = filter_var($filter['unlimited'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $string = static fn (string $key): ?string => isset($filter[$key]) && is_string($filter[$key]) && trim($filter[$key]) !== '' ? trim($filter[$key]) : null;

        return new self($page, $perPage, $string('search'), $string('status'), $string('category'), $string('billing_cycle'), $unlimited, $sort, $descending);
    }
}
