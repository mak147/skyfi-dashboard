<?php

declare(strict_types=1);

namespace SkyFi\Shared\Http;

/**
 * Normalizes pagination at the HTTP boundary while retaining support for both
 * JSON:API page[number]/page[size] and the legacy page/per_page parameters.
 */
final class PaginationInput
{
    public const MAX_PAGE_SIZE = 100;

    /** @param array<string, mixed> $query */
    public static function page(array $query, int $default = 1): int
    {
        $page = $query['page'] ?? null;
        $value = is_array($page) ? ($page['number'] ?? $default) : ($page ?? $default);

        return max(1, is_numeric($value) ? (int) $value : $default);
    }

    /** @param array<string, mixed> $query */
    public static function perPage(array $query, int $default = 15): int
    {
        $page = $query['page'] ?? null;
        $value = is_array($page) ? ($page['size'] ?? $query['per_page'] ?? $default) : ($query['per_page'] ?? $default);
        $size = is_numeric($value) ? (int) $value : $default;

        return max(1, min(self::MAX_PAGE_SIZE, $size));
    }
}
