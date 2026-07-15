<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\EventRegistryEntry;

interface EventRegistryRepositoryContract
{
    /** @return array{items: list<EventRegistryEntry>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(int $page = 1, int $perPage = 25, ?string $sourceModule = null): array;

    public function find(int $id): ?EventRegistryEntry;

    public function findByKey(string $eventKey): ?EventRegistryEntry;

    public function create(array $data): EventRegistryEntry;

    public function update(int $id, array $data): ?EventRegistryEntry;

    /** @return list<string> */
    public function allActiveKeys(): array;

    /** @return list<string> */
    public function sourceModules(): array;
}
