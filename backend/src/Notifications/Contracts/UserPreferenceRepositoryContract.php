<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Contracts;

use SkyFi\Notifications\DomainModels\UserNotificationPreference;

interface UserPreferenceRepositoryContract
{
    /** @return list<UserNotificationPreference> */
    public function listForUser(int $userId): array;

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<UserNotificationPreference>
     */
    public function replaceForUser(int $userId, array $rows): array;

    public function isChannelEnabled(int $userId, string $channel, string $category, bool $isTransactional): bool;

    /** @return array{start: ?string, end: ?string, timezone: ?string}|null */
    public function quietHours(int $userId, string $channel): ?array;
}
