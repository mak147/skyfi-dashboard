<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Repositories;

use PDO;
use SkyFi\Notifications\Contracts\NotificationTemplateRepositoryContract;
use SkyFi\Notifications\DomainModels\NotificationTemplate;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoNotificationTemplateRepository implements NotificationTemplateRepositoryContract
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(array $filters = []): array
    {
        $where = ['deleted_at IS NULL'];
        $params = [];
        if (($filters['category'] ?? '') !== '') {
            $where[] = 'category = :category';
            $params['category'] = $filters['category'];
        }
        if (($filters['channel'] ?? '') !== '') {
            $where[] = 'channel = :channel';
            $params['channel'] = $filters['channel'];
        }
        if (($filters['code'] ?? '') !== '') {
            $where[] = 'code = :code';
            $params['code'] = $filters['code'];
        }
        if (($filters['search'] ?? '') !== '') {
            $where[] = '(name LIKE :search OR code LIKE :search OR body_template LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'is_active = :is_active';
            $params['is_active'] = (int) filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
        }

        $page = max(1, (int) ($filters['page']['number'] ?? $filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['page']['size'] ?? $filters['per_page'] ?? 25)));
        $whereSql = implode(' AND ', $where);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM notification_templates WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM notification_templates WHERE {$whereSql} ORDER BY category ASC, code ASC, channel ASC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(
                static fn (array $row): NotificationTemplate => NotificationTemplate::fromRow($row),
                $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
            ),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'lastPage' => (int) max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function find(int $id): ?NotificationTemplate
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notification_templates WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? NotificationTemplate::fromRow($row) : null;
    }

    public function findByCodeChannel(string $code, string $channel, string $locale = 'en'): ?NotificationTemplate
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM notification_templates
             WHERE code = :code AND channel = :channel AND locale = :locale AND is_active = 1 AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['code' => $code, 'channel' => $channel, 'locale' => $locale]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return NotificationTemplate::fromRow($row);
        }
        if ($locale !== 'en') {
            return $this->findByCodeChannel($code, $channel, 'en');
        }

        return null;
    }

    public function create(array $data, ?int $actorId = null): NotificationTemplate
    {
        if ($actorId !== null) {
            $data['created_by'] = $actorId;
            $data['updated_by'] = $actorId;
        }
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables'], JSON_THROW_ON_ERROR);
        }
        $columns = array_keys($data);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);
        $stmt = $this->pdo->prepare(
            'INSERT INTO notification_templates (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($data);
        $id = (int) $this->pdo->lastInsertId();
        $found = $this->find($id);
        if (!$found) {
            throw new NotFoundException('Template was not created.');
        }

        return $found;
    }

    public function update(int $id, array $data, ?int $actorId = null): NotificationTemplate
    {
        if ($this->find($id) === null) {
            throw new NotFoundException('Notification template not found.');
        }
        if ($actorId !== null) {
            $data['updated_by'] = $actorId;
        }
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables'], JSON_THROW_ON_ERROR);
        }
        if ($data !== []) {
            $sets = array_map(static fn (string $c): string => "{$c} = :{$c}", array_keys($data));
            $stmt = $this->pdo->prepare(
                'UPDATE notification_templates SET ' . implode(',', $sets) . ' WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute($data + ['id' => $id]);
        }
        $found = $this->find($id);
        if (!$found) {
            throw new NotFoundException('Notification template not found.');
        }

        return $found;
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notification_templates SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
