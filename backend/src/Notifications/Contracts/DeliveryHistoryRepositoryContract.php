<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Contracts;

use SkyFi\Notifications\DomainModels\DeliveryHistory;
use SkyFi\Notifications\DTOs\DeliveryListFilters;

interface DeliveryHistoryRepositoryContract
{
    /** @return array{items: list<DeliveryHistory>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(DeliveryListFilters $filters): array;

    public function find(int $id): ?DeliveryHistory;

    /** @param array<string, mixed> $data */
    public function create(array $data): DeliveryHistory;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): DeliveryHistory;
}
