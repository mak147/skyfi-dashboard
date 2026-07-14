<?php

declare(strict_types=1);

namespace SkyFi\Connections\Contracts;

use SkyFi\Connections\Data\CreateConnectionData;
use SkyFi\Connections\Data\UpdateConnectionData;
use SkyFi\Connections\Data\ConnectionListFilters;
use SkyFi\Connections\Models\Connection;

interface ConnectionServiceContract
{
    public function list(ConnectionListFilters $filters): array;
    public function get(int $id): Connection;
    public function create(CreateConnectionData $data, int $authUserId, ?string $ip, ?string $ua): Connection;
    public function update(int $id, UpdateConnectionData $data, int $authUserId, ?string $ip, ?string $ua): Connection;
    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void;
    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Connection;
}
