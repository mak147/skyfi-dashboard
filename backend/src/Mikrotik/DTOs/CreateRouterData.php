<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DTOs;

final class CreateRouterData
{
    /** @param array<int, int> $tagIds */
    public function __construct(
        public readonly string $name,
        public readonly string $host,
        public readonly int $apiPort,
        public readonly string $apiUsername,
        public readonly string $apiPassword,
        public readonly ?int $routerGroupId,
        public readonly array $tagIds,
        public readonly ?string $location,
        public readonly ?string $site,
        public readonly ?string $notes,
        public readonly bool $isEnabled,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) ($data['name'] ?? '')),
            host: trim((string) ($data['host'] ?? '')),
            apiPort: isset($data['api_port']) ? (int) $data['api_port'] : 8729,
            apiUsername: trim((string) ($data['api_username'] ?? '')),
            apiPassword: (string) ($data['api_password'] ?? ''),
            routerGroupId: self::nullableInt($data['router_group_id'] ?? null),
            tagIds: self::integerList($data['tag_ids'] ?? []),
            location: self::nullableString($data['location'] ?? null),
            site: self::nullableString($data['site'] ?? null),
            notes: self::nullableString($data['notes'] ?? null),
            isEnabled: !isset($data['is_enabled']) || filter_var($data['is_enabled'], FILTER_VALIDATE_BOOLEAN),
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @return array<int, int> */
    private static function integerList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0,
            $value,
        ), static fn (int $id): bool => $id > 0)));
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }
}
