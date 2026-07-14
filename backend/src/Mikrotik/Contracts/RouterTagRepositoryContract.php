<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DomainModels\RouterTag;

interface RouterTagRepositoryContract
{
    /** @return array<int, RouterTag> */
    public function all(): array;

    public function find(int $id): ?RouterTag;

    /** @param array<int, int> $ids @return array<int, int> */
    public function existingIds(array $ids): array;

    public function existsByName(string $name, ?int $excludeId = null): bool;

    /** @param array<string, mixed> $data */
    public function create(array $data): RouterTag;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): RouterTag;

    public function delete(int $id): void;
}
