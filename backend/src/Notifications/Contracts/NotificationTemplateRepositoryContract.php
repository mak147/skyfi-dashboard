<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Contracts;

use SkyFi\Notifications\DomainModels\NotificationTemplate;

interface NotificationTemplateRepositoryContract
{
    /** @param array<string, mixed> $filters @return array{items: list<NotificationTemplate>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(array $filters = []): array;

    public function find(int $id): ?NotificationTemplate;

    public function findByCodeChannel(string $code, string $channel, string $locale = 'en'): ?NotificationTemplate;

    /** @param array<string, mixed> $data */
    public function create(array $data, ?int $actorId = null): NotificationTemplate;

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data, ?int $actorId = null): NotificationTemplate;

    public function softDelete(int $id): bool;
}
