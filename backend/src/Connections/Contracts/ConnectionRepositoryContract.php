<?php

declare(strict_types=1);

namespace SkyFi\Connections\Contracts;

use SkyFi\Connections\Data\ConnectionListFilters;
use SkyFi\Connections\Models\Connection;

interface ConnectionRepositoryContract
{
    public function list(ConnectionListFilters $filters): array;
    public function find(int $id): ?Connection;
    public function findByNumber(string $number): ?Connection;
    public function existsByPppoeUsername(string $username, ?int $excludeId = null): bool;
    public function create(array $data): Connection;
    public function update(int $id, array $data): Connection;
    public function updateStatus(int $id, string $status): void;
    public function softDelete(int $id): void;
}
