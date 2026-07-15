<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Contracts;

use SkyFi\Inventory\DomainModels\StockMovement;
use SkyFi\Inventory\DTOs\StockMovementListFilters;
use SkyFi\Inventory\DTOs\StockOperationData;

interface StockRepositoryContract
{
    /** @return array{items: array<int, StockMovement>, total: int, page: int, perPage: int, lastPage: int} */
    public function list(StockMovementListFilters $filters): array;
    public function find(int $id, bool $forUpdate = false): ?StockMovement;
    public function post(StockOperationData $data, int $actorId): StockMovement;
    public function reverse(int $id, string $reason, int $actorId): StockMovement;
    /** @return array<int, array<string, mixed>> */
    public function balances(array $filters): array;
    /** @return array<string, mixed> */
    public function dashboard(): array;
    /** @return array<string, mixed> */
    public function accountingSettings(): array;
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function updateAccountingSettings(array $data, int $actorId): array;
    /** @return array<int, array<string, mixed>> */
    public function financePostings(): array;
    public function transaction(callable $callback): mixed;
}
