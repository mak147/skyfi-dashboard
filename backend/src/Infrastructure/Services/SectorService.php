<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Services;

use SkyFi\Infrastructure\Contracts\NetworkDeviceRepositoryContract;
use SkyFi\Infrastructure\Contracts\SectorRepositoryContract;
use SkyFi\Infrastructure\Contracts\SectorServiceContract;
use SkyFi\Infrastructure\Contracts\TowerRepositoryContract;
use SkyFi\Infrastructure\Data\CreateSectorData;
use SkyFi\Infrastructure\Data\SectorListFilters;
use SkyFi\Infrastructure\Data\UpdateSectorData;
use SkyFi\Infrastructure\Models\Sector;
use SkyFi\Infrastructure\Validators\SectorValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class SectorService implements SectorServiceContract
{
    /** @var array<string> */
    private const VALID_STATUSES = ['planning', 'active', 'maintenance', 'decommissioned'];

    /** @var array<string, array<string>> */
    private const VALID_STATUS_TRANSITIONS = [
        'planning' => ['active', 'decommissioned'],
        'active' => ['maintenance', 'decommissioned'],
        'maintenance' => ['active', 'decommissioned'],
        'decommissioned' => ['planning'],
    ];

    public function __construct(
        private readonly SectorRepositoryContract $repository,
        private readonly TowerRepositoryContract $towerRepository,
        private readonly NetworkDeviceRepositoryContract $deviceRepository,
        private readonly SectorValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(SectorListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): Sector
    {
        $sector = $this->repository->findActive($id);
        if ($sector === null) {
            throw new NotFoundException('Sector not found.');
        }

        return $sector;
    }

    public function create(CreateSectorData $data, int $authUserId, ?string $ip, ?string $ua): Sector
    {
        $this->validator->validateCreate($data);

        // Verify tower exists
        $tower = $this->towerRepository->findActive($data->towerId);
        if ($tower === null) {
            throw new ValidationException([
                ['code' => 'not_found', 'detail' => 'The selected tower does not exist.', 'source' => ['pointer' => '/data/attributes/tower_id']],
            ]);
        }

        // Verify device exists if provided
        if ($data->deviceId !== null) {
            $device = $this->deviceRepository->findActive($data->deviceId);
            if ($device === null) {
                throw new ValidationException([
                    ['code' => 'not_found', 'detail' => 'The selected device does not exist.', 'source' => ['pointer' => '/data/attributes/device_id']],
                ]);
            }
            if ($device->deviceType !== 'access_point' && $device->deviceType !== 'radio') {
                throw new ValidationException([
                    ['code' => 'invalid_type', 'detail' => 'Device must be an Access Point or Radio.', 'source' => ['pointer' => '/data/attributes/device_id']],
                ]);
            }
        }

        $sector = $this->repository->create([
            'tower_id' => $data->towerId,
            'name' => $data->name,
            'azimuth' => $data->azimuth,
            'beamwidth' => $data->beamwidth,
            'frequency_mhz' => $data->frequencyMhz,
            'channel_width_mhz' => $data->channelWidthMhz,
            'ssid' => $data->ssid,
            'eirp_dbm' => $data->eirpDbm,
            'device_id' => $data->deviceId,
            'capacity_mbps' => $data->capacityMbps,
            'max_subscribers' => $data->maxSubscribers,
            'status' => $data->status,
            'notes' => $data->notes,
            'created_by' => $authUserId,
            'updated_by' => null,
        ]);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'create',
            entityType: 'sector',
            entityId: $sector->id,
            oldValues: null,
            newValues: $sector->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $sector;
    }

    public function update(int $id, UpdateSectorData $data, int $authUserId, ?string $ip, ?string $ua): Sector
    {
        $existing = $this->get($id);
        $this->validator->validateUpdate($data);

        $updateData = $data->toArray();

        if (!empty($updateData)) {
            if (isset($updateData['tower_id'])) {
                $tower = $this->towerRepository->findActive($updateData['tower_id']);
                if ($tower === null) {
                    throw new ValidationException([
                        ['code' => 'not_found', 'detail' => 'The selected tower does not exist.', 'source' => ['pointer' => '/data/attributes/tower_id']],
                    ]);
                }
            }

            if (isset($updateData['device_id']) && $updateData['device_id'] !== null) {
                $device = $this->deviceRepository->findActive($updateData['device_id']);
                if ($device === null) {
                    throw new ValidationException([
                        ['code' => 'not_found', 'detail' => 'The selected device does not exist.', 'source' => ['pointer' => '/data/attributes/device_id']],
                    ]);
                }
                if ($device->deviceType !== 'access_point' && $device->deviceType !== 'radio') {
                    throw new ValidationException([
                        ['code' => 'invalid_type', 'detail' => 'Device must be an Access Point or Radio.', 'source' => ['pointer' => '/data/attributes/device_id']],
                    ]);
                }
            }
        }

        $oldValues = $existing->toArray();

        $sector = $this->repository->update($id, $updateData);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update',
            entityType: 'sector',
            entityId: $id,
            oldValues: $oldValues,
            newValues: $sector->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $sector;
    }

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void
    {
        $existing = $this->get($id);

        $this->repository->softDelete($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'delete',
            entityType: 'sector',
            entityId: $id,
            oldValues: $existing->toArray(),
            newValues: null,
            ipAddress: $ip,
            userAgent: $ua,
        );
    }

    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Sector
    {
        $sector = $this->get($id);

        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            throw new ValidationException([
                ['code' => 'invalid_status', 'detail' => 'The provided status is not valid.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$sector->status] ?? [];
        if (!in_array($newStatus, $allowedTransitions, true)) {
            throw new ValidationException([
                ['code' => 'invalid_transition', 'detail' => "Cannot transition from '{$sector->status}' to '{$newStatus}'.", 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $oldValues = ['status' => $sector->status];

        $this->repository->updateStatus($id, $newStatus);
        $updated = $this->get($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'status_change',
            entityType: 'sector',
            entityId: $id,
            oldValues: $oldValues,
            newValues: ['status' => $newStatus],
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $updated;
    }

    public function getByTower(int $towerId): array
    {
        return $this->repository->getByTower($towerId);
    }

    public function getWithConnectionCount(int $id): Sector
    {
        $sector = $this->repository->getWithConnectionCount($id);
        if ($sector === null) {
            throw new NotFoundException('Sector not found.');
        }
        return $sector;
    }

    public function getCoverageData(): array
    {
        return $this->repository->getCoverageData();
    }
}