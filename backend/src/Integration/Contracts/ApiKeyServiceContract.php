<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\ApiKey;
use SkyFi\Integration\DTOs\ApiKeyListFilters;
use SkyFi\Integration\DTOs\CreateApiKeyData;
use SkyFi\Integration\DTOs\UpdateApiKeyData;

interface ApiKeyServiceContract
{
    /** @return array{items: list<ApiKey>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(int $userId, ApiKeyListFilters $filters): array;

    public function get(int $id, int $userId): ApiKey;

    /** @return array{key: ApiKey, plain_text_key: string} */
    public function create(int $userId, CreateApiKeyData $data): array;

    public function update(int $id, int $userId, UpdateApiKeyData $data): ApiKey;

    public function delete(int $id, int $userId): void;

    /** @return array{key: ApiKey, plain_text_key: string} */
    public function regenerate(int $id, int $userId): array;

    public function authenticate(string $plainKey): ?ApiKey;

    /** @return list<ApiKey> */
    public function keysForApplication(int $applicationId): array;
}
