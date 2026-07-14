<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DTOs\CreateRouterData;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\DTOs\UpdateRouterData;
use SkyFi\Mikrotik\DomainModels\Router;
use SkyFi\Mikrotik\DomainModels\RouterConnectionData;

interface RouterServiceContract
{
    /** @return array{items: array<int, Router>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(RouterListFilters $filters): array;

    public function get(int $id): Router;

    public function create(CreateRouterData $data, int $actorId, ?string $ip, ?string $userAgent): Router;

    public function update(int $id, UpdateRouterData $data, int $actorId, ?string $ip, ?string $userAgent): Router;

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void;

    public function setEnabled(int $id, bool $isEnabled, int $actorId, ?string $ip, ?string $userAgent): Router;

    /** @param array<int, int> $tagIds */
    public function syncTags(int $id, array $tagIds, int $actorId, ?string $ip, ?string $userAgent): Router;

    public function connectionData(int $id): RouterConnectionData;
}
