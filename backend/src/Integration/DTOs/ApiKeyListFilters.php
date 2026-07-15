<?php

declare(strict_types=1);

namespace SkyFi\Integration\DTOs;

final class ApiKeyListFilters
{
    public function __construct(
        public readonly ?int $clientApplicationId = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /** @param array<string, mixed> $query */
    public static function fromQuery(array $query): self
    {
        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);

        return new self(
            clientApplicationId: isset($query['client_application_id']) ? (int) $query['client_application_id'] : null,
            isActive: isset($query['is_active']) ? filter_var($query['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null,
            search: isset($query['search']) && $query['search'] !== '' ? (string) $query['search'] : null,
            page: max(1, $page),
            perPage: max(1, min(100, $perPage)),
        );
    }
}
