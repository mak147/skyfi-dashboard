<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Repositories;

use PDO;
use SkyFi\Monitoring\Contracts\DeviceStatusRepositoryContract;
use SkyFi\Monitoring\DomainModels\DeviceStatusHistory;

final class PdoDeviceStatusRepository implements DeviceStatusRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function recordStatus(
        string $deviceType,
        int $deviceId,
        string $status,
        ?float $latencyMs = null,
        ?string $errorMessage = null,
    ): DeviceStatusHistory {
        $stmt = $this->pdo->prepare(
            'INSERT INTO monitoring_device_status_history (device_type, device_id, status, latency_ms, error_message, checked_at)
             VALUES (:device_type, :device_id, :status, :latency_ms, :error_message, :checked_at)'
        );
        $checkedAt = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'device_type' => $deviceType,
            'device_id' => $deviceId,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'error_message' => $errorMessage !== null ? substr($errorMessage, 0, 500) : null,
            'checked_at' => $checkedAt,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        return $this->find($id) ?? throw new \RuntimeException('Failed to retrieve device status record.');
    }

    /** @return array<int, DeviceStatusHistory> */
    public function getHistoryForDevice(string $deviceType, int $deviceId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM monitoring_device_status_history WHERE device_type = :device_type AND device_id = :device_id ORDER BY checked_at DESC, id DESC LIMIT :limit'
        );
        $stmt->bindValue('device_type', $deviceType);
        $stmt->bindValue('device_id', $deviceId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch()) {
            $items[] = DeviceStatusHistory::fromRow($row);
        }

        return $items;
    }

    public function getLatestForDevice(string $deviceType, int $deviceId): ?DeviceStatusHistory
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM monitoring_device_status_history WHERE device_type = :device_type AND device_id = :device_id ORDER BY checked_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['device_type' => $deviceType, 'device_id' => $deviceId]);
        $row = $stmt->fetch();

        return $row === false ? null : DeviceStatusHistory::fromRow($row);
    }

    private function find(int $id): ?DeviceStatusHistory
    {
        $stmt = $this->pdo->prepare('SELECT * FROM monitoring_device_status_history WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : DeviceStatusHistory::fromRow($row);
    }
}
