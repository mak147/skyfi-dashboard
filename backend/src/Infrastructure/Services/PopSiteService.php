<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Services;

use SkyFi\Infrastructure\Contracts\PopSiteRepositoryContract;
use SkyFi\Infrastructure\Contracts\PopSiteServiceContract;
use SkyFi\Infrastructure\Data\CreatePopSiteData;
use SkyFi\Infrastructure\Data\PopSiteListFilters;
use SkyFi\Infrastructure\Data\UpdatePopSiteData;
use SkyFi\Infrastructure\Models\PopSite;
use SkyFi\Infrastructure\Validators\PopSiteValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class PopSiteService implements PopSiteServiceContract
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
        private readonly PopSiteRepositoryContract $repository,
        private readonly PopSiteValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(PopSiteListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): PopSite
    {
        $popSite = $this->repository->findActive($id);
        if ($popSite === null) {
            throw new NotFoundException('POP site not found.');
        }

        return $popSite;
    }

    public function create(CreatePopSiteData $data, int $authUserId, ?string $ip, ?string $ua): PopSite
    {
        $this->validator->validateCreate($data);

        $this->validateUniqueFields($data->code, $data->name, null);

        $popSite = $this->repository->create([
            'name' => $data->name,
            'code' => $data->code,
            'address_line1' => $data->addressLine1,
            'address_line2' => $data->addressLine2,
            'city' => $data->city,
            'region' => $data->region,
            'country' => $data->country ?? 'Pakistan',
            'gps_latitude' => $data->gpsLatitude,
            'gps_longitude' => $data->gpsLongitude,
            'contact_person' => $data->contactPerson,
            'contact_phone' => $data->contactPhone,
            'contact_email' => $data->contactEmail,
            'power_status' => $data->powerStatus,
            'fiber_provider' => $data->fiberProvider,
            'status' => $data->status,
            'notes' => $data->notes,
            'created_by' => $authUserId,
            'updated_by' => null,
        ]);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'create',
            entityType: 'pop_site',
            entityId: $popSite->id,
            oldValues: null,
            newValues: $popSite->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $popSite;
    }

    public function update(int $id, UpdatePopSiteData $data, int $authUserId, ?string $ip, ?string $ua): PopSite
    {
        $existing = $this->get($id);
        $this->validator->validateUpdate($data);

        $updateData = $data->toArray();

        if (!empty($updateData)) {
            $this->validateUniqueFields(
                $updateData['code'] ?? null,
                $updateData['name'] ?? null,
                $id
            );
        }

        $oldValues = $existing->toArray();

        $popSite = $this->repository->update($id, $updateData);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update',
            entityType: 'pop_site',
            entityId: $id,
            oldValues: $oldValues,
            newValues: $popSite->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $popSite;
    }

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void
    {
        $existing = $this->get($id);

        $this->repository->softDelete($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'delete',
            entityType: 'pop_site',
            entityId: $id,
            oldValues: $existing->toArray(),
            newValues: null,
            ipAddress: $ip,
            userAgent: $ua,
        );
    }

    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): PopSite
    {
        $popSite = $this->get($id);

        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            throw new ValidationException([
                ['code' => 'invalid_status', 'detail' => 'The provided status is not valid.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$popSite->status] ?? [];
        if (!in_array($newStatus, $allowedTransitions, true)) {
            throw new ValidationException([
                ['code' => 'invalid_transition', 'detail' => "Cannot transition from '{$popSite->status}' to '{$newStatus}'.", 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $oldValues = ['status' => $popSite->status];

        $this->repository->updateStatus($id, $newStatus);
        $updated = $this->get($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'status_change',
            entityType: 'pop_site',
            entityId: $id,
            oldValues: $oldValues,
            newValues: ['status' => $newStatus],
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $updated;
    }

    public function getTowers(int $popSiteId): array
    {
        return $this->repository->getTowersForPopSite($popSiteId);
    }

    public function getMapPoints(): array
    {
        return $this->repository->getMapPoints();
    }

    private function validateUniqueFields(?string $code, ?string $name, ?int $excludeId): void
    {
        $errors = [];

        if ($code !== null && $this->repository->codeExists($code, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'The code has already been taken.', 'source' => ['pointer' => '/data/attributes/code']];
        }

        if ($name !== null && $this->repository->nameExists($name, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'The name has already been taken.', 'source' => ['pointer' => '/data/attributes/name']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}