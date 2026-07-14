<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Repositories;

use PDO;
use SkyFi\Monitoring\Contracts\AlertRepositoryContract;
use SkyFi\Monitoring\DomainModels\AlertHistoryItem;
use SkyFi\Monitoring\DomainModels\NetworkAlert;
use SkyFi\Monitoring\DTOs\AlertListFilters;
use SkyFi\Monitoring\DTOs\CreateAlertData;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PdoAlertRepository implements AlertRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createAlert(CreateAlertData $data): NetworkAlert
    {
        $metadataJson = $data->metadata !== null ? json_encode($data->metadata) : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO monitoring_alerts (
                alert_type, severity, status, device_type, device_id, title, description,
                metric_value, threshold_value, metadata, triggered_at
            ) VALUES (
                :alert_type, :severity, :status, :device_type, :device_id, :title, :description,
                :metric_value, :threshold_value, :metadata, :triggered_at
            )'
        );
        $triggeredAt = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'alert_type' => $data->alertType,
            'severity' => $data->severity,
            'status' => 'new',
            'device_type' => $data->deviceType,
            'device_id' => $data->deviceId,
            'title' => substr($data->title, 0, 255),
            'description' => $data->description,
            'metric_value' => $data->metricValue !== null ? substr($data->metricValue, 0, 100) : null,
            'threshold_value' => $data->thresholdValue !== null ? substr($data->thresholdValue, 0, 100) : null,
            'metadata' => $metadataJson,
            'triggered_at' => $triggeredAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $alert = $this->findAlert($id) ?? throw new \RuntimeException('Failed to retrieve created alert.');

        $this->recordHistoryItem($id, null, 'new', null, 'Alert automatically triggered.');

        return $alert;
    }

    public function findAlert(int $id): ?NetworkAlert
    {
        $stmt = $this->pdo->prepare('SELECT * FROM monitoring_alerts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : NetworkAlert::fromRow($row);
    }

    /** @return array{items: array<int, NetworkAlert>, total: int, page: int, per_page: int} */
    public function listAlerts(AlertListFilters $filters): array
    {
        $conditions = [];
        $params = [];

        if ($filters->status !== null) {
            $conditions[] = 'status = :status';
            $params['status'] = $filters->status;
        }
        if ($filters->severity !== null) {
            $conditions[] = 'severity = :severity';
            $params['severity'] = $filters->severity;
        }
        if ($filters->deviceType !== null) {
            $conditions[] = 'device_type = :device_type';
            $params['device_type'] = $filters->deviceType;
        }
        if ($filters->deviceId !== null) {
            $conditions[] = 'device_id = :device_id';
            $params['device_id'] = $filters->deviceId;
        }

        $whereClause = $conditions !== [] ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM monitoring_alerts {$whereClause}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($filters->page - 1) * $filters->perPage;
        $stmt = $this->pdo->prepare(
            "SELECT * FROM monitoring_alerts {$whereClause} ORDER BY triggered_at DESC, id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $filters->perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = NetworkAlert::fromRow($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $filters->page,
            'per_page' => $filters->perPage,
        ];
    }

    public function updateAlertStatus(
        int $alertId,
        string $newStatus,
        ?int $actorId,
        ?string $notes,
    ): NetworkAlert {
        $existing = $this->findAlert($alertId);
        if ($existing === null) {
            throw new NotFoundException('Alert not found.');
        }

        $oldStatus = $existing->status;
        $now = gmdate('Y-m-d H:i:s');
        $updates = ['status = :status'];
        $params = [
            'id' => $alertId,
            'status' => $newStatus,
        ];

        if ($newStatus === 'acknowledged') {
            $updates[] = 'acknowledged_at = :now, acknowledged_by = :actor_id';
            $params['now'] = $now;
            $params['actor_id'] = $actorId;
        } elseif ($newStatus === 'resolved') {
            $updates[] = 'resolved_at = :now, resolved_by = :actor_id';
            $params['now'] = $now;
            $params['actor_id'] = $actorId;
        } elseif ($newStatus === 'dismissed') {
            $updates[] = 'dismissed_at = :now, dismissed_by = :actor_id';
            $params['now'] = $now;
            $params['actor_id'] = $actorId;
        }

        if ($notes !== null) {
            $updates[] = 'resolution_notes = :notes';
            $params['notes'] = substr($notes, 0, 500);
        }

        $sql = 'UPDATE monitoring_alerts SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $this->recordHistoryItem($alertId, $oldStatus, $newStatus, $actorId, $notes);

        return $this->findAlert($alertId) ?? throw new \RuntimeException('Failed to retrieve updated alert.');
    }

    public function recordHistoryItem(
        int $alertId,
        ?string $oldStatus,
        string $newStatus,
        ?int $changedBy,
        ?string $notes,
    ): AlertHistoryItem {
        $stmt = $this->pdo->prepare(
            'INSERT INTO monitoring_alert_history (alert_id, old_status, new_status, changed_by, notes, created_at)
             VALUES (:alert_id, :old_status, :new_status, :changed_by, :notes, :created_at)'
        );
        $createdAt = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'alert_id' => $alertId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'notes' => $notes !== null ? substr($notes, 0, 500) : null,
            'created_at' => $createdAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->findHistoryItem($id) ?? throw new \RuntimeException('Failed to retrieve alert history item.');
    }

    /** @return array<int, AlertHistoryItem> */
    public function getHistoryForAlert(int $alertId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM monitoring_alert_history WHERE alert_id = :alert_id ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute(['alert_id' => $alertId]);

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = AlertHistoryItem::fromRow($row);
        }

        return $items;
    }

    /** @return array{new: int, acknowledged: int, critical: int, warning: int} */
    public function getAlertCounts(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
                SUM(CASE WHEN status = 'acknowledged' THEN 1 ELSE 0 END) AS ack_count,
                SUM(CASE WHEN status IN ('new', 'acknowledged') AND severity = 'critical' THEN 1 ELSE 0 END) AS critical_count,
                SUM(CASE WHEN status IN ('new', 'acknowledged') AND severity = 'warning' THEN 1 ELSE 0 END) AS warning_count
             FROM monitoring_alerts"
        );
        $row = $stmt->fetch();

        return [
            'new' => (int) ($row['new_count'] ?? 0),
            'acknowledged' => (int) ($row['ack_count'] ?? 0),
            'critical' => (int) ($row['critical_count'] ?? 0),
            'warning' => (int) ($row['warning_count'] ?? 0),
        ];
    }

    private function findHistoryItem(int $id): ?AlertHistoryItem
    {
        $stmt = $this->pdo->prepare('SELECT * FROM monitoring_alert_history WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : AlertHistoryItem::fromRow($row);
    }
}
