<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Contracts;

use SkyFi\Pppoe\DomainModels\PppoeAccount;
use SkyFi\Pppoe\DTOs\PppoeListFilters;

interface PppoeAccountRepositoryContract
{
    /** @return array{items: array<int, PppoeAccount>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(PppoeListFilters $filters): array;

    public function find(int $id): ?PppoeAccount;

    public function findByUsername(string $username): ?PppoeAccount;

    public function existsByUsername(string $username, ?int $excludeId = null): bool;

    /** @return array<int, PppoeAccount> */
    public function listByRouter(int $routerId): array;

    /** @return array<int, PppoeAccount> */
    public function listByCustomer(int $customerId): array;

    /** @return array<int, PppoeAccount> */
    public function listByConnection(int $connectionId): array;

    /** @param array<string, mixed> $data */
    public function insert(array $data): PppoeAccount;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): PppoeAccount;

    public function delete(int $id): void;

    public function updateSyncStatus(int $id, string $syncStatus): void;
}
