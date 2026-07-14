<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\DomainModels\Router;

interface RouterRepositoryContract
{
    /** @return array{items: array<int, Router>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(RouterListFilters $filters): array;

    public function find(int $id): ?Router;

    public function existsByName(string $name, ?int $excludeId = null): bool;

    /** @param array<string, mixed> $data */
    public function create(array $data): Router;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): Router;

    public function softDelete(int $id): void;

    /** @param array<int, int> $tagIds */
    public function syncTags(int $routerId, array $tagIds): void;

    /** @param array<string, mixed> $status */
    public function updateConnectionStatus(int $routerId, array $status): void;

    /** @param array<string, mixed> $metadata */
    public function updateDiscoveryMetadata(int $routerId, array $metadata): void;
}
