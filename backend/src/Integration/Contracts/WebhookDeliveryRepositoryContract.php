<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\WebhookDelivery;
use SkyFi\Integration\DTOs\DeliveryListFilters;

interface WebhookDeliveryRepositoryContract
{
    /** @return array{items: list<WebhookDelivery>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(DeliveryListFilters $filters): array;

    public function find(int $id): ?WebhookDelivery;

    public function create(array $data): WebhookDelivery;

    public function update(int $id, array $data): ?WebhookDelivery;

    /** @return list<WebhookDelivery> */
    public function findPendingRetries(): array;
}
