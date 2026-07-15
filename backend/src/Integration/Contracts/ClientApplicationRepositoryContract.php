<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\ClientApplication;

interface ClientApplicationRepositoryContract
{
    /** @return array{items: list<ClientApplication>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(int $page = 1, int $perPage = 25): array;

    public function find(int $id): ?ClientApplication;

    public function create(array $data): ClientApplication;

    public function update(int $id, array $data): ?ClientApplication;

    public function delete(int $id): bool;
}
