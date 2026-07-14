<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Services;

use SkyFi\Infrastructure\Contracts\PopSiteRepositoryContract;
use SkyFi\Infrastructure\Contracts\TowerRepositoryContract;
use SkyFi\Infrastructure\Contracts\TowerServiceContract;
use SkyFi\Infrastructure\Data\CreateTowerData;
use SkyFi\Infrastructure\Data\TowerListFilters;
use SkyFi\Infrastructure\Data\UpdateTowerData;
use SkyFi\Infrastructure\Models\Tower;
use SkyFi\Infrastructure\Validators\TowerValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class TowerService implements TowerServiceContract
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
        private readonly TowerRepositoryContract $repository,
        private readonly PopSiteRepositoryContract $popSiteRepository,
        private readonly TowerValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(TowerListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): Tower
    {
        $tower = $this->repository->findActive($id);
        if ($tower === null) {
            throw new NotFoundException('Tower not found.');
        }

        return $tower;
    }

    public function create(CreateTowerData $data, int $authUserId, ?string $ip, ?string $ua): Tower
    {
        $this->validator->validateCreate($data);

        // Verify POP site exists
        $popSite = $this->popSiteRepository->findActive($data->popSiteId);
        if ($popSite === null) {
            throw new ValidationException([
                ['code' => 'not_found', 'detail' => 'The selected POP site does not exist.', 'source' => ['pointer' => '/data/attributes/pop_site_id']],
            ]);
        }

        $this->validateUniqueFields($data->code, $data->name, $data->popSiteId, null);

        $tower = $this->repository->create([
            'pop_site_id' => $data->popSiteId,
            'name' => $data->name,
            'code' => $data->code,
            'tower_type' => $data->towerType,
            'height_meters' => $data->heightMeters,
            'owner' => $data->owner,
            'address_line1' => $data->addressLine1,
            'city' => $data->city,
            'region' => $data->region,
            'gps_latitude' => $data->gpsLatitude,
            'gps_longitude' => $data->gpsLongitude,
            'status' => $data->status,
            'notes' => $data->notes,
            'created_by' => $authUserId,
            'updated_by' => null,
        ]);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'create',
            entityType: 'tower',
            entityId: $tower->id,
            oldValues: null,
            newValues: $tower->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $tower;
    }

    public function update(int $id, UpdateTowerData $data, int $authUserId, ?string $ip, ?string $ua): Tower
    {
        $existing = $this->get($id);
        $this->validator->validateUpdate($data);

        $updateData = $data->toArray();

        if (!empty($updateData)) {
            if (isset($updateData['pop_site_id'])) {
                $popSite = $this->popSiteRepository->findActive($updateData['pop_site_id']);
                if ($popSite === null) {
                    throw new ValidationException([
                        ['code' => 'not_found', 'detail' => 'The selected POP site does not exist.', 'source' => ['pointer' => '/data/attributes/pop_site_id']],
                    ]);
                }
            }

            $popSiteId = $updateData['pop_site_id'] ?? $existing->popSiteId;
            $this->validateUniqueFields(
                $updateData['code'] ?? null,
                $updateData['name'] ?? null,
                $popSiteId,
                $id
            );
        }

        $oldValues = $existing->toArray();

        $tower = $this->repository->update($id, $updateData);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update',
            entityType: 'tower',
            entityId: $id,
            oldValues: $oldValues,
            newValues: $tower->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $tower;
    }

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void
    {
        $existing = $this->get($id);

        $this->repository->softDelete($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'delete',
            entityType: 'tower',
            entityId: $id,
            oldValues: $existing->toArray(),
            newValues: null,
            ipAddress: $ip,
            userAgent: $ua,
        );
    }

    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Tower
    {
        $tower = $this->get($id);

        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            throw new ValidationException([
                ['code' => 'invalid_status', 'detail' => 'The provided status is not valid.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$tower->status] ?? [];
        if (!in_array($newStatus, $allowedTransitions, true)) {
            throw new ValidationException([
                ['code' => 'invalid_transition', 'detail' => "Cannot transition from '{$tower->status}' to '{$newStatus}'.", 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $oldValues = ['status' => $tower->status];

        $this->repository->updateStatus($id, $newStatus);
        $updated = $this->get($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'status_change',
            entityType: 'tower',
            entityId: $id,
            oldValues: $oldValues,
            newValues: ['status' => $newStatus],
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $updated;
    }

    public function getSectors(int $towerId): array
    {
        return $this->repository->getSectorsForTower($towerId);
    }

    public function getDevices(int $towerId): array
    {
        return $this->repository->getDevicesForTower($towerId);
    }

    public function getMapPoints(): array
    {
        return $this->repository->getMapPoints();
    }

    public function getByPopSite(int $popSiteId): array
    {
        return $this->repository->getByPopSite($popSiteId);
    }

    private function validateUniqueFields(?string $code, ?string $name, int $popSiteId, ?int $excludeId): void
    {
        $errors = [];

        if ($code !== null && $this->repository->codeExists($code, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'The code has already been taken.', 'source' => ['pointer' => '/data/attributes/code']];
        }

        if ($name !== null && $this->repository->nameExistsInPopSite($popSiteId, $name, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'A tower with this name already exists in this POP site.', 'source' => ['pointer' => '/data/attributes/name']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}