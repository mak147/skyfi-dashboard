<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Repositories;

use PDO;
use SkyFi\Notifications\Contracts\NotificationRepositoryContract;
use SkyFi\Notifications\DomainModels\Notification;
use SkyFi\Notifications\DTOs\NotificationListFilters;

final class PdoNotificationRepository implements NotificationRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function listForUser(int $userId, NotificationListFilters $filters): array
    {
        $where = ['recipient_user_id = :user_id', 'deleted_at IS NULL'];
        $params = ['user_id' => $userId];

        if ($filters->status !== null) {
            $where[] = 'status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->category !== null) {
            $where[] = 'category = :category';
            $params['category'] = $filters->category;
        }
        if ($filters->type !== null) {
            $where[] = 'notification_type = :type';
            $params['type'] = $filters->type;
        }
        if ($filters->severity !== null) {
            $where[] = 'severity = :severity';
            $params['severity'] = $filters->severity;
        }
        if ($filters->search !== null) {
            $where[] = '(title LIKE :search OR body LIKE :search)';
            $params['search'] = '%' . $filters->search . '%';
        }

        $whereSql = implode(' AND ', $where);
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($filters->page - 1) * $filters->perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM notifications WHERE {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = array_map(
            static fn (array $row): Notification => Notification::fromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );

        return [
            'items' => $items,
            'page' => $filters->page,
            'perPage' => $filters->perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / $filters->perPage)),
        ];
    }

    public function findForUser(int $id, int $userId): ?Notification
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM notifications WHERE id = :id AND recipient_user_id = :user_id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Notification::fromRow($row) : null;
    }

    public function create(array $data): Notification
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $stmt->bindValue($key, json_encode($value, JSON_THROW_ON_ERROR));
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $id = (int) $this->pdo->lastInsertId();
        $fetch = $this->pdo->prepare('SELECT * FROM notifications WHERE id = :id');
        $fetch->execute(['id' => $id]);

        return Notification::fromRow($fetch->fetch(PDO::FETCH_ASSOC) ?: ['id' => $id] + $data);
    }

    public function markRead(int $id, int $userId): ?Notification
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET status = 'read', read_at = COALESCE(read_at, CURRENT_TIMESTAMP), updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND recipient_user_id = :user_id AND deleted_at IS NULL"
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        return $this->findForUser($id, $userId);
    }

    public function markAllRead(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET status = 'read', read_at = COALESCE(read_at, CURRENT_TIMESTAMP), updated_at = CURRENT_TIMESTAMP
             WHERE recipient_user_id = :user_id AND status = 'unread' AND deleted_at IS NULL"
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->rowCount();
    }

    public function archive(int $id, int $userId): ?Notification
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notifications SET status = 'archived', updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND recipient_user_id = :user_id AND deleted_at IS NULL"
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        return $this->findForUser($id, $userId);
    }

    public function softDelete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND recipient_user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications WHERE recipient_user_id = :user_id AND status = 'unread' AND deleted_at IS NULL"
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }
}
