<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Repositories;

use PDO;
use SkyFi\Monitoring\Contracts\EventLoggingRepositoryContract;
use SkyFi\Monitoring\DomainModels\MonitoringEvent;
use SkyFi\Monitoring\DTOs\EventLogListFilters;
use SkyFi\Monitoring\DTOs\LogMonitoringEventData;

final class PdoEventLoggingRepository implements EventLoggingRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function logEvent(LogMonitoringEventData $data): MonitoringEvent
    {
        $metadataJson = $data->metadata !== null ? json_encode($data->metadata) : null;
        $statement = $this->pdo->prepare(
            'INSERT INTO monitoring_events (event_type, severity, source_type, source_id, message, metadata, created_at)
             VALUES (:event_type, :severity, :source_type, :source_id, :message, :metadata, :created_at)'
        );
        $createdAt = gmdate('Y-m-d H:i:s');
        $statement->execute([
            'event_type' => $data->eventType,
            'severity' => $data->severity,
            'source_type' => $data->sourceType,
            'source_id' => $data->sourceId,
            'message' => substr($data->message, 0, 500),
            'metadata' => $metadataJson,
            'created_at' => $createdAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? throw new \RuntimeException('Failed to retrieve created monitoring event.');
    }

    /** @return array{items: array<int, MonitoringEvent>, total: int, page: int, per_page: int} */
    public function listEvents(EventLogListFilters $filters): array
    {
        $conditions = [];
        $params = [];

        if ($filters->eventType !== null) {
            $conditions[] = 'event_type = :event_type';
            $params['event_type'] = $filters->eventType;
        }
        if ($filters->severity !== null) {
            $conditions[] = 'severity = :severity';
            $params['severity'] = $filters->severity;
        }
        if ($filters->sourceType !== null) {
            $conditions[] = 'source_type = :source_type';
            $params['source_type'] = $filters->sourceType;
        }
        if ($filters->sourceId !== null) {
            $conditions[] = 'source_id = :source_id';
            $params['source_id'] = $filters->sourceId;
        }

        $whereClause = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM monitoring_events {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $stmt = $this->pdo->prepare(
            "SELECT * FROM monitoring_events {$whereClause} ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = MonitoringEvent::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'per_page' => $filters->perPage,
        ];
    }

    private function find(int $id): ?MonitoringEvent
    {
        $stmt = $this->pdo->prepare('SELECT * FROM monitoring_events WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : MonitoringEvent::fromRow($row);
    }
}
