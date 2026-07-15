<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Repositories;

use PDO;
use SkyFi\Notifications\Contracts\UserPreferenceRepositoryContract;
use SkyFi\Notifications\DomainModels\UserNotificationPreference;

final class PdoUserPreferenceRepository implements UserPreferenceRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM user_notification_preferences WHERE user_id = :user_id ORDER BY channel ASC, category ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map(
            static fn (array $row): UserNotificationPreference => UserNotificationPreference::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );
    }

    public function replaceForUser(int $userId, array $rows): array
    {
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM user_notification_preferences WHERE user_id = :user_id');
            $delete->execute(['user_id' => $userId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO user_notification_preferences
                (user_id, channel, category, is_enabled, quiet_hours_start, quiet_hours_end, quiet_hours_timezone)
                VALUES (:user_id, :channel, :category, :is_enabled, :quiet_hours_start, :quiet_hours_end, :quiet_hours_timezone)'
            );
            foreach ($rows as $row) {
                $insert->execute([
                    'user_id' => $userId,
                    'channel' => $row['channel'],
                    'category' => $row['category'] ?: '*',
                    'is_enabled' => (int) ($row['is_enabled'] ?? 1),
                    'quiet_hours_start' => $row['quiet_hours_start'] ?: null,
                    'quiet_hours_end' => $row['quiet_hours_end'] ?: null,
                    'quiet_hours_timezone' => $row['quiet_hours_timezone'] ?: null,
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->listForUser($userId);
    }

    public function isChannelEnabled(int $userId, string $channel, string $category, bool $isTransactional): bool
    {
        if ($isTransactional) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            'SELECT is_enabled FROM user_notification_preferences
             WHERE user_id = :user_id AND channel = :channel AND category IN (:category, \'*\')
             ORDER BY CASE WHEN category = :category2 THEN 0 ELSE 1 END
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'channel' => $channel,
            'category' => $category,
            'category2' => $category,
        ]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            // Default: all channels enabled when no preference row exists
            return true;
        }

        return (int) $value === 1;
    }

    public function quietHours(int $userId, string $channel): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT quiet_hours_start, quiet_hours_end, quiet_hours_timezone
             FROM user_notification_preferences
             WHERE user_id = :user_id AND channel = :channel AND quiet_hours_start IS NOT NULL AND quiet_hours_end IS NOT NULL
             ORDER BY CASE WHEN category = \'*\' THEN 0 ELSE 1 END
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'channel' => $channel]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'start' => $row['quiet_hours_start'],
            'end' => $row['quiet_hours_end'],
            'timezone' => $row['quiet_hours_timezone'],
        ];
    }
}
