<?php

declare(strict_types=1);

namespace SkyFi\Vendors\DTOs;

final class SupplierListFilters
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $search,
        public readonly ?string $status,
        public readonly ?int $categoryId,
        public readonly ?string $country,
        public readonly ?float $minimumRating,
        public readonly bool $includeArchived,
        public readonly string $sort,
    ) {
    }

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $filters = is_array($query['filter'] ?? null) ? $query['filter'] : $query;
        $page = is_array($query['page'] ?? null) ? $query['page'] : [];
        $text = static function (mixed $value): ?string {
            $value = trim((string) ($value ?? ''));
            return $value === '' ? null : $value;
        };
        $status = $text($filters['status'] ?? null);

        return new self(
            max(1, (int) ($page['number'] ?? $query['page'] ?? 1)),
            min(100, max(1, (int) ($page['size'] ?? $query['per_page'] ?? 20))),
            $text($filters['search'] ?? $query['search'] ?? null),
            $status,
            isset($filters['category_id']) && $filters['category_id'] !== '' ? (int) $filters['category_id'] : null,
            $text($filters['country'] ?? null),
            isset($filters['minimum_rating']) && $filters['minimum_rating'] !== '' ? (float) $filters['minimum_rating'] : null,
            filter_var($filters['include_archived'] ?? $query['include_archived'] ?? $status === 'archived', FILTER_VALIDATE_BOOLEAN),
            (string) ($query['sort'] ?? '-created_at'),
        );
    }
}
