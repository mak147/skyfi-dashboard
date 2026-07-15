<?php

declare(strict_types=1);

namespace SkyFi\Integration\Contracts;

use SkyFi\Integration\DomainModels\Webhook;
use SkyFi\Integration\DTOs\WebhookListFilters;

interface WebhookRepositoryContract
{
    /** @return array{items: list<Webhook>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(WebhookListFilters $filters): array;

    public function find(int $id): ?Webhook;

    /** @return list<Webhook> */
    public function findActiveByEvent(string $eventKey): array;

    /** @return list<Webhook> */
    public function findInboundByEventType(string $eventType): array;

    public function create(array $data): Webhook;

    public function update(int $id, array $data): ?Webhook;

    public function delete(int $id): bool;
}
