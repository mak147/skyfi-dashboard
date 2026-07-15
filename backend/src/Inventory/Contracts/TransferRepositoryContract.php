<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Contracts;

use SkyFi\Inventory\DomainModels\WarehouseTransfer;
use SkyFi\Inventory\DTOs\TransferData;
use SkyFi\Inventory\DTOs\TransferListFilters;

interface TransferRepositoryContract
{
    /** @return array{items: array<int, WarehouseTransfer>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(TransferListFilters $filters): array;
    public function find(int $id, bool $forUpdate = false): ?WarehouseTransfer;
    public function create(TransferData $data, int $actorId): WarehouseTransfer;
    public function update(int $id, TransferData $data, int $actorId): WarehouseTransfer;
    public function transition(int $id, string $action, array $data, int $actorId): WarehouseTransfer;
    public function delete(int $id): void;
    public function transaction(callable $callback): mixed;
}
