<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DomainModels\RouterGroup;

interface RouterGroupRepositoryContract
{
    /** @return array<int, RouterGroup> */
    public function all(): array;

    public function find(int $id): ?RouterGroup;

    public function existsByName(string $name, ?int $excludeId = null): bool;

    /** @param array<string, mixed> $data */
    public function create(array $data): RouterGroup;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): RouterGroup;

    public function hasActiveRouters(int $id): bool;

    public function delete(int $id): void;
}
