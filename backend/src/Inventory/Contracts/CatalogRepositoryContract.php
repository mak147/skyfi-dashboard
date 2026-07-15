<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Contracts;

interface CatalogRepositoryContract
{
    /** @return array<int, array<string, mixed>> */
    public function list(string $resource, bool $activeOnly = false): array;
    /** @return array<string, mixed>|null */
    public function find(string $resource, int $id): ?array;
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function create(string $resource, array $data, int $actorId): array;
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function update(string $resource, int $id, array $data, int $actorId): array;
    public function delete(string $resource, int $id, int $actorId): void;
    /** @return array<int, array<string, mixed>> */
    public function lookup(string $resource, string $search): array;
}
