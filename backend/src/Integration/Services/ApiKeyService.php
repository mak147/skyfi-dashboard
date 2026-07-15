<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\ApiKeyRepositoryContract;
use SkyFi\Integration\Contracts\ApiKeyServiceContract;
use SkyFi\Integration\DomainModels\ApiKey;
use SkyFi\Integration\DTOs\ApiKeyListFilters;
use SkyFi\Integration\DTOs\CreateApiKeyData;
use SkyFi\Integration\DTOs\UpdateApiKeyData;
use SkyFi\Shared\Exceptions\NotFoundException;

final class ApiKeyService implements ApiKeyServiceContract
{
    public function __construct(
        private readonly ApiKeyRepositoryContract $keys,
        private readonly ApiKeyManager $manager,
    ) {}

    public function list(int $userId, ApiKeyListFilters $filters): array
    {
        return $this->keys->list($filters);
    }

    public function get(int $id, int $userId): ApiKey
    {
        return $this->keys->find($id)
            ?? throw new NotFoundException('API key not found.');
    }

    public function create(int $userId, CreateApiKeyData $data): array
    {
        $generated = $this->manager->generate();

        $key = $this->keys->create([
            'client_application_id' => $data->clientApplicationId,
            'name' => $data->name,
            'key_prefix' => $generated['key_prefix'],
            'key_hash' => $generated['key_hash'],
            'scopes' => $data->scopes,
            'ip_allow_list' => $data->ipAllowList,
            'is_active' => true,
            'rate_limit_per_minute' => $data->rateLimitPerMinute,
            'expires_at' => $data->expiresAt,
            'created_by' => $userId,
        ]);

        return [
            'key' => $key,
            'plain_text_key' => $generated['plain_text'],
        ];
    }

    public function update(int $id, int $userId, UpdateApiKeyData $data): ApiKey
    {
        $this->get($id, $userId);
        $updateData = [];
        if ($data->name !== null) {
            $updateData['name'] = $data->name;
        }
        if ($data->scopes !== null) {
            $updateData['scopes'] = $data->scopes;
        }
        if ($data->ipAllowList !== null) {
            $updateData['ip_allow_list'] = $data->ipAllowList;
        }
        if ($data->isActive !== null) {
            $updateData['is_active'] = $data->isActive;
        }
        if ($data->rateLimitPerMinute !== null) {
            $updateData['rate_limit_per_minute'] = $data->rateLimitPerMinute;
        }
        if ($data->expiresAt !== null) {
            $updateData['expires_at'] = $data->expiresAt === '' ? null : $data->expiresAt;
        }

        return $this->keys->update($id, $updateData)
            ?? throw new NotFoundException('API key not found after update.');
    }

    public function delete(int $id, int $userId): void
    {
        if (!$this->keys->delete($id)) {
            throw new NotFoundException('API key not found.');
        }
    }

    public function regenerate(int $id, int $userId): array
    {
        $existing = $this->get($id, $userId);
        $generated = $this->manager->generate();

        $key = $this->keys->update($id, [
            'key_prefix' => $generated['key_prefix'],
            'key_hash' => $generated['key_hash'],
        ]) ?? throw new NotFoundException('API key not found after regeneration.');

        return [
            'key' => $key,
            'plain_text_key' => $generated['plain_text'],
        ];
    }

    public function authenticate(string $plainKey): ?ApiKey
    {
        if (!$this->manager->isValidFormat($plainKey)) {
            return null;
        }
        $hash = $this->manager->hash($plainKey);
        $key = $this->keys->findByHash($hash);
        if ($key !== null) {
            $this->keys->updateLastUsed($key->id());
        }

        return $key;
    }

    public function keysForApplication(int $applicationId): array
    {
        $result = $this->keys->list(new ApiKeyListFilters(
            clientApplicationId: $applicationId,
            page: 1,
            perPage: 100,
        ));

        return $result['items'];
    }
}
