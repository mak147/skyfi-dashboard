<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Contracts;

use SkyFi\Notifications\DomainModels\NotificationEvent;

interface NotificationEventRepositoryContract
{
    /** @param array<string, mixed> $filters @return array{items: list<NotificationEvent>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(array $filters = []): array;

    public function find(int $id): ?NotificationEvent;

    public function findByUuid(string $uuid): ?NotificationEvent;

    /** @param array<string, mixed> $data */
    public function create(array $data): NotificationEvent;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): NotificationEvent;
}
