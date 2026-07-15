<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Contracts;

use SkyFi\Notifications\DomainModels\Notification;
use SkyFi\Notifications\DTOs\DispatchNotificationData;
use SkyFi\Notifications\DTOs\NotificationListFilters;

interface NotificationServiceContract
{
    /** @return array{items: list<Notification>, page: int, perPage: int, total: int, lastPage: int} */
    public function list(int $userId, NotificationListFilters $filters): array;

    public function get(int $id, int $userId): Notification;

    public function markRead(int $id, int $userId): Notification;

    public function markAllRead(int $userId): int;

    public function archive(int $id, int $userId): Notification;

    public function delete(int $id, int $userId): void;

    public function unreadCount(int $userId): int;

    /** @return array<string, mixed> */
    public function dispatch(DispatchNotificationData $data): array;

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function fromDomain(string $eventKey, array $payload): array;

    /** @return array<string, mixed> */
    public function catalog(): array;
}
