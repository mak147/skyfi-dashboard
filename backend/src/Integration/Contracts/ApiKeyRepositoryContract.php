<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\ApiKey;
use SkyFi\Integration\DTOs\ApiKeyListFilters;

interface ApiKeyRepositoryContract
{
    /** @return array{items: list<ApiKey>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(ApiKeyListFilters $filters): array;

    public function find(int $id): ?ApiKey;

    public function findByHash(string $keyHash): ?ApiKey;

    public function findByPrefix(string $prefix): ?ApiKey;

    public function create(array $data): ApiKey;

    public function update(int $id, array $data): ?ApiKey;

    public function delete(int $id): bool;

    public function updateLastUsed(int $id): void;
}
