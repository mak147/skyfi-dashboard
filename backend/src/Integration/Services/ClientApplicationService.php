<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\ClientApplicationRepositoryContract;
use SkyFi\Integration\DomainModels\ClientApplication;
use SkyFi\Integration\DTOs\CreateClientApplicationData;
use SkyFi\Integration\DTOs\UpdateClientApplicationData;
use SkyFi\Shared\Exceptions\NotFoundException;

final class ClientApplicationService
{
    public function __construct(
        private readonly ClientApplicationRepositoryContract $apps,
    ) {}

    /** @return array{items: list<ClientApplication>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(int $page = 1, int $perPage = 25): array
    {
        return $this->apps->list($page, $perPage);
    }

    public function get(int $id): ClientApplication
    {
        return $this->apps->find($id)
            ?? throw new NotFoundException('Client application not found.');
    }

    public function create(int $userId, CreateClientApplicationData $data): ClientApplication
    {
        return $this->apps->create([
            'name' => $data->name,
            'description' => $data->description,
            'redirect_uris' => $data->redirectUris ?? [],
            'rate_limit_per_minute' => $data->rateLimitPerMinute,
            'is_active' => true,
            'created_by' => $userId,
        ]);
    }

    public function update(int $id, int $userId, UpdateClientApplicationData $data): ClientApplication
    {
        $this->get($id);
        $updateData = [];
        if ($data->name !== null) {
            $updateData['name'] = $data->name;
        }
        if ($data->description !== null) {
            $updateData['description'] = $data->description;
        }
        if ($data->redirectUris !== null) {
            $updateData['redirect_uris'] = $data->redirectUris;
        }
        if ($data->isActive !== null) {
            $updateData['is_active'] = $data->isActive;
        }
        if ($data->rateLimitPerMinute !== null) {
            $updateData['rate_limit_per_minute'] = $data->rateLimitPerMinute;
        }

        return $this->apps->update($id, $updateData)
            ?? throw new NotFoundException('Client application not found after update.');
    }

    public function delete(int $id): void
    {
        if (!$this->apps->delete($id)) {
            throw new NotFoundException('Client application not found.');
        }
    }
}
