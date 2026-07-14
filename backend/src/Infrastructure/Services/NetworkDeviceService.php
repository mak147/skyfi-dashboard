<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Services;

use SkyFi\Infrastructure\Contracts\Mikrotik\Contracts\RouterRepositoryContract;
use SkyFi\Infrastructure\Contracts\NetworkDeviceRepositoryContract;
use SkyFi\Infrastructure\Contracts\NetworkDeviceServiceContract;
use SkyFi\Infrastructure\Contracts\PopSiteRepositoryContract;
use SkyFi\Infrastructure\Contracts\TowerRepositoryContract;
use SkyFi\Infrastructure\Data\CreateNetworkDeviceData;
use SkyFi\Infrastructure\Data\NetworkDeviceListFilters;
use SkyFi\Infrastructure\Data\UpdateNetworkDeviceData;
use SkyFi\Infrastructure\Models\NetworkDevice;
use SkyFi\Infrastructure\Validators\NetworkDeviceValidator;
use SkyFi\Mikrotik\Contracts\RouterRepositoryContract as MikrotikRouterRepositoryContract;
use SkyFi\Mikrotik\Services\CredentialCipher;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class NetworkDeviceService implements NetworkDeviceServiceContract
{
    /** @var array<string> */
    private const VALID_STATUSES = ['inventory', 'deployed', 'maintenance', 'offline', 'decommissioned'];

    /** @var array<string, array<string>> */
    private const VALID_STATUS_TRANSITIONS = [
        'inventory' => ['deployed', 'maintenance', 'decommissioned'],
        'deployed' => ['maintenance', 'offline', 'decommissioned'],
        'maintenance' => ['deployed', 'offline', 'decommissioned'],
        'offline' => ['deployed', 'maintenance', 'decommissioned'],
        'decommissioned' => ['inventory'],
    ];

    public function __construct(
        private readonly NetworkDeviceRepositoryContract $repository,
        private readonly PopSiteRepositoryContract $popSiteRepository,
        private readonly TowerRepositoryContract $towerRepository,
        private readonly MikrotikRouterRepositoryContract $mikrotikRouterRepository,
        private readonly CredentialCipher $credentialCipher,
        private readonly NetworkDeviceValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(NetworkDeviceListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): NetworkDevice
    {
        $device = $this->repository->findActive($id);
        if ($device === null) {
            throw new NotFoundException('Network device not found.');
        }

        return $device;
    }

    public function create(CreateNetworkDeviceData $data, int $authUserId, ?string $ip, ?string $ua): NetworkDevice
    {
        $this->validator->validateCreate($data);

        // Verify POP site exists if provided
        if ($data->popSiteId !== null) {
            $popSite = $this->popSiteRepository->findActive($data->popSiteId);
            if ($popSite === null) {
                throw new ValidationException([
                    ['code' => 'not_found', 'detail' => 'The selected POP site does not exist.', 'source' => ['pointer' => '/data/attributes/pop_site_id']],
                ]);
            }
        }

        // Verify tower exists if provided
        if ($data->towerId !== null) {
            $tower = $this->towerRepository->findActive($data->towerId);
            if ($tower === null) {
                throw new ValidationException([
                    ['code' => 'not_found', 'detail' => 'The selected tower does not exist.', 'source' => ['pointer' => '/data/attributes/tower_id']],
                ]);
            }

            // If both provided, verify tower belongs to POP site
            if ($data->popSiteId !== null && $tower->popSiteId !== $data->popSiteId) {
                throw new ValidationException([
                    ['code' => 'mismatch', 'detail' => 'The selected tower does not belong to the selected POP site.', 'source' => ['pointer' => '/data/attributes/tower_id']],
                ]);
            }
        }

        // Verify MikroTik router exists if provided
        if ($data->mikrotikRouterId !== null) {
            $router = $this->mikrotikRouterRepository->find($data->mikrotikRouterId);
            if ($router === null) {
                throw new ValidationException([
                    ['code' => 'not_found', 'detail' => 'The selected MikroTik router does not exist.', 'source' => ['pointer' => '/data/attributes/mikrotik_router_id']],
                ]);
            }
        }

        // Check unique constraints
        $this->validateUniqueFields($data->serialNumber, $data->macAddress, $data->ipAddress, null);

        // Encrypt password if provided
        $encryptedPassword = null;
        if ($data->managementPassword !== null && $data->managementPassword !== '') {
            $encryptedPassword = $this->credentialCipher->encrypt($data->managementPassword);
        }

        $device = $this->repository->create([
            'pop_site_id' => $data->popSiteId,
            'tower_id' => $data->towerId,
            'name' => $data->name,
            'device_type' => $data->deviceType,
            'vendor' => $data->vendor,
            'model' => $data->model,
            'serial_number' => $data->serialNumber,
            'mac_address' => $data->macAddress,
            'ip_address' => $data->ipAddress,
            'firmware_version' => $data->firmwareVersion,
            'location_description' => $data->locationDescription,
            'management_vlan' => $data->managementVlan,
            'management_username' => $data->managementUsername,
            'management_password_encrypted' => $encryptedPassword,
            'status' => $data->status,
            'notes' => $data->notes,
            'mikrotik_router_id' => $data->mikrotikRouterId,
            'created_by' => $authUserId,
            'updated_by' => null,
        ]);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'create',
            entityType: 'network_device',
            entityId: $device->id,
            oldValues: null,
            newValues: $device->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $device;
    }

    public function update(int $id, UpdateNetworkDeviceData $data, int $authUserId, ?string $ip, ?string $ua): NetworkDevice
    {
        $existing = $this->get($id);
        $this->validator->validateUpdate($data);

        $updateData = $data->toArray();

        if (!empty($updateData)) {
            if (isset($updateData['pop_site_id']) && $updateData['pop_site_id'] !== null) {
                $popSite = $this->popSiteRepository->findActive($updateData['pop_site_id']);
                if ($popSite === null) {
                    throw new ValidationException([
                        ['code' => 'not_found', 'detail' => 'The selected POP site does not exist.', 'source' => ['pointer' => '/data/attributes/pop_site_id']],
                    ]);
                }
            }

            if (isset($updateData['tower_id']) && $updateData['tower_id'] !== null) {
                $tower = $this->towerRepository->findActive($updateData['tower_id']);
                if ($tower === null) {
                    throw new ValidationException([
                        ['code' => 'not_found', 'detail' => 'The selected tower does not exist.', 'source' => ['pointer' => '/data/attributes/tower_id']],
                    ]);
                }

                $popSiteId = $updateData['pop_site_id'] ?? $existing->popSiteId;
                if ($popSiteId !== null && $tower->popSiteId !== $popSiteId) {
                    throw new ValidationException([
                        ['code' => 'mismatch', 'detail' => 'The selected tower does not belong to the selected POP site.', 'source' => ['pointer' => '/data/attributes/tower_id']],
                    ]);
                }
            }

            if (isset($updateData['mikrotik_router_id']) && $updateData['mikrotik_router_id'] !== null) {
                $router = $this->mikrotikRouterRepository->find($updateData['mikrotik_router_id']);
                if ($router === null) {
                    throw new ValidationException([
                        ['code' => 'not_found', 'detail' => 'The selected MikroTik router does not exist.', 'source' => ['pointer' => '/data/attributes/mikrotik_router_id']],
                    ]);
                }
            }

            // Check unique constraints
            $this->validateUniqueFields(
                $updateData['serial_number'] ?? null,
                $updateData['mac_address'] ?? null,
                $updateData['ip_address'] ?? null,
                $id
            );

            // Encrypt password if provided
            if (isset($updateData['management_password']) && $updateData['management_password'] !== null) {
                if ($updateData['management_password'] !== '') {
                    $updateData['management_password_encrypted'] = $this->credentialCipher->encrypt($updateData['management_password']);
                } else {
                    $updateData['management_password_encrypted'] = null;
                }
                unset($updateData['management_password']);
            }
        }

        $oldValues = $existing->toArray();

        $device = $this->repository->update($id, $updateData);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update',
            entityType: 'network_device',
            entityId: $id,
            oldValues: $oldValues,
            newValues: $device->toArray(),
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $device;
    }

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void
    {
        $existing = $this->get($id);

        $this->repository->softDelete($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'delete',
            entityType: 'network_device',
            entityId: $id,
            oldValues: $existing->toArray(),
            newValues: null,
            ipAddress: $ip,
            userAgent: $ua,
        );
    }

    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): NetworkDevice
    {
        $device = $this->get($id);

        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            throw new ValidationException([
                ['code' => 'invalid_status', 'detail' => 'The provided status is not valid.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $allowedTransitions = self::VALID_STATUS_TRANSITIONS[$device->status] ?? [];
        if (!in_array($newStatus, $allowedTransitions, true)) {
            throw new ValidationException([
                ['code' => 'invalid_transition', 'detail' => "Cannot transition from '{$device->status}' to '{$newStatus}'.", 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $oldValues = ['status' => $device->status];

        $this->repository->updateStatus($id, $newStatus);
        $updated = $this->get($id);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'status_change',
            entityType: 'network_device',
            entityId: $id,
            oldValues: $oldValues,
            newValues: ['status' => $newStatus],
            ipAddress: $ip,
            userAgent: $ua,
        );

        return $updated;
    }

    public function getByPopSite(int $popSiteId): array
    {
        return $this->repository->getByPopSite($popSiteId);
    }

    public function getByTower(int $towerId): array
    {
        return $this->repository->getByTower($towerId);
    }

    public function getByType(string $type): array
    {
        return $this->repository->getByType($type);
    }

    private function validateUniqueFields(?string $serial, ?string $mac, ?string $ip, ?int $excludeId): void
    {
        $errors = [];

        if ($serial !== null && $this->repository->serialExists($serial, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'The serial number has already been taken.', 'source' => ['pointer' => '/data/attributes/serial_number']];
        }

        if ($mac !== null && $this->repository->macExists($mac, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'The MAC address has already been taken.', 'source' => ['pointer' => '/data/attributes/mac_address']];
        }

        if ($ip !== null && $this->repository->ipExists($ip, $excludeId)) {
            $errors[] = ['code' => 'unique', 'detail' => 'The IP address has already been taken.', 'source' => ['pointer' => '/data/attributes/ip_address']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}