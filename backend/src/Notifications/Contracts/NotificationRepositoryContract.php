<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Contracts;

use SkyFi\Notifications\DomainModels\Notification;
use SkyFi\Notifications\DTOs\NotificationListFilters;

interface NotificationRepositoryContract
{
    /** @return array{items: list<Notification>, page: int, perPage: int, total: int, lastPage: int} */
    public function listForUser(int $userId, NotificationListFilters $filters): array;

    public function findForUser(int $id, int $userId): ?Notification;

    /** @param array<string, mixed> $data */
    public function create(array $data): Notification;

    public function markRead(int $id, int $userId): ?Notification;

    public function markAllRead(int $userId): int;

    public function archive(int $id, int $userId): ?Notification;

    public function softDelete(int $id, int $userId): bool;

    public function unreadCount(int $userId): int;
}
