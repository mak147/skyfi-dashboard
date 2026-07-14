<?php

declare(strict_types=1);

namespace SkyFi\Connections\Services;

use SkyFi\Connections\Contracts\ConnectionRepositoryContract;
use SkyFi\Connections\Contracts\ConnectionServiceContract;
use SkyFi\Connections\Data\ConnectionListFilters;
use SkyFi\Connections\Data\CreateConnectionData;
use SkyFi\Connections\Data\UpdateConnectionData;
use SkyFi\Connections\Models\Connection;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class ConnectionService implements ConnectionServiceContract
{
    private const VALID_STATUSES = ['pending', 'scheduled', 'installing', 'active', 'suspended', 'disconnected', 'cancelled', 'archived'];

    public function __construct(
        private readonly ConnectionRepositoryContract $repository,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(ConnectionListFilters $filters): array
    {
        return $this->repository->list($filters);
    }

    public function get(int $id): Connection
    {
        $connection = $this->repository->find($id);
        if (!$connection) {
            throw new NotFoundException('Connection not found.');
        }
        return $connection;
    }

    public function create(CreateConnectionData $data, int $authUserId, ?string $ip, ?string $ua): Connection
    {
        if ($data->pppoeUsername && $this->repository->existsByPppoeUsername($data->pppoeUsername)) {
            throw new ValidationException([
                ['code' => 'unique', 'detail' => 'PPPoE Username already exists.', 'source' => ['pointer' => '/data/attributes/pppoe_username']]
            ]);
        }

        $connectionNumber = $this->generateConnectionNumber();

        $connection = $this->repository->create([
            'connection_number' => $connectionNumber,
            'name' => $data->name,
            'customer_id' => $data->customerId,
            'package_id' => $data->packageId,
            'type' => $data->type,
            'status' => 'pending',
            'pppoe_username' => $data->pppoeUsername,
            'pppoe_password' => $data->pppoePassword, // Should be encrypted in a real app
            'static_ip' => $data->staticIp,
            'gateway' => $data->gateway,
            'dns_servers' => $data->dnsServers,
            'mac_address' => $data->macAddress,
            'vlan_id' => $data->vlanId,
            'radius_profile' => $data->radiusProfile,
            'queue_name' => $data->queueName,
            'pop_site' => $data->popSite,
            'tower' => $data->tower,
            'sector' => $data->sector,
            'access_point' => $data->accessPoint,
            'assigned_router' => $data->assignedRouter,
            'installation_date' => $data->installationDate,
            'installation_team' => $data->installationTeam,
            'technician_id' => $data->technicianId,
            'installation_cost' => $data->installationCost,
            'installation_notes' => $data->installationNotes,
            'created_by' => $authUserId,
        ]);

        $this->auditLogger->log($authUserId, 'create', 'connection', $connection->id(), null, $connection->toArray(), $ip, $ua);

        return $connection;
    }

    public function update(int $id, UpdateConnectionData $data, int $authUserId, ?string $ip, ?string $ua): Connection
    {
        $existing = $this->get($id);

        if ($data->pppoeUsername && $this->repository->existsByPppoeUsername($data->pppoeUsername, $id)) {
            throw new ValidationException([
                ['code' => 'unique', 'detail' => 'PPPoE Username already exists.', 'source' => ['pointer' => '/data/attributes/pppoe_username']]
            ]);
        }

        $connection = $this->repository->update($id, array_filter([
            'name' => $data->name,
            'package_id' => $data->packageId,
            'customer_id' => $data->customerId,
            'type' => $data->type,
            'pppoe_username' => $data->pppoeUsername,
            'pppoe_password' => $data->pppoePassword,
            'static_ip' => $data->staticIp,
            'gateway' => $data->gateway,
            'dns_servers' => $data->dnsServers,
            'mac_address' => $data->macAddress,
            'vlan_id' => $data->vlanId,
            'radius_profile' => $data->radiusProfile,
            'queue_name' => $data->queueName,
            'pop_site' => $data->popSite,
            'tower' => $data->tower,
            'sector' => $data->sector,
            'access_point' => $data->accessPoint,
            'assigned_router' => $data->assignedRouter,
            'installation_date' => $data->installationDate,
            'installation_team' => $data->installationTeam,
            'technician_id' => $data->technicianId,
            'installation_cost' => $data->installationCost,
            'installation_notes' => $data->installationNotes,
            'updated_by' => $authUserId,
        ], fn($v) => $v !== null));

        $this->auditLogger->log($authUserId, 'update', 'connection', $id, $existing->toArray(), $connection->toArray(), $ip, $ua);

        return $connection;
    }

    public function delete(int $id, int $authUserId, ?string $ip, ?string $ua): void
    {
        $existing = $this->get($id);
        $this->repository->softDelete($id);
        $this->auditLogger->log($authUserId, 'delete', 'connection', $id, $existing->toArray(), null, $ip, $ua);
    }

    public function changeStatus(int $id, string $newStatus, int $authUserId, ?string $ip, ?string $ua): Connection
    {
        $existing = $this->get($id);
        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            throw new ValidationException([
                ['code' => 'invalid', 'detail' => 'Invalid status.', 'source' => ['pointer' => '/data/attributes/status']]
            ]);
        }

        $this->repository->updateStatus($id, $newStatus);
        $updated = $this->get($id);

        $this->auditLogger->log($authUserId, 'status_change', 'connection', $id, ['status' => $existing->toArray()['status']], ['status' => $newStatus], $ip, $ua);

        return $updated;
    }

    private function generateConnectionNumber(): string
    {
        $prefix = 'CON-';
        do {
            $number = $prefix . strtoupper(bin2hex(random_bytes(4)));
        } while ($this->repository->findByNumber($number));
        return $number;
    }
}
