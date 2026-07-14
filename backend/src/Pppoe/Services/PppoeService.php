<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Services;

use SkyFi\Connections\Contracts\ConnectionRepositoryContract;
use SkyFi\Customers\Contracts\CustomerRepositoryContract;
use SkyFi\Mikrotik\Contracts\CredentialCipherContract;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Packages\Contracts\PackageRepositoryContract;
use SkyFi\Pppoe\Contracts\PppoeAccountRepositoryContract;
use SkyFi\Pppoe\Contracts\PppoeServiceContract;
use SkyFi\Pppoe\Contracts\PppoeSyncLoggerContract;
use SkyFi\Pppoe\DomainModels\PppoeAccount;
use SkyFi\Pppoe\DTOs\CreatePppoeAccountData;
use SkyFi\Pppoe\DTOs\PppoeListFilters;
use SkyFi\Pppoe\DTOs\UpdatePppoeAccountData;
use SkyFi\Pppoe\Validators\PppoeValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class PppoeService implements PppoeServiceContract
{
    public function __construct(
        private readonly PppoeAccountRepositoryContract $accounts,
        private readonly CustomerRepositoryContract $customers,
        private readonly ConnectionRepositoryContract $connections,
        private readonly PackageRepositoryContract $packages,
        private readonly RouterServiceContract $routerService,
        private readonly MikrotikConnectionPoolContract $pool,
        private readonly CredentialCipherContract $cipher,
        private readonly PppoeSyncLoggerContract $syncLogger,
        private readonly PppoeValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(PppoeListFilters $filters): array
    {
        $result = $this->accounts->list($filters);

        $enrichedItems = [];
        foreach ($result['items'] as $account) {
            $enrichedItems[] = $this->enrichAccountInfo($account);
        }

        return [
            ...$result,
            'items' => $enrichedItems,
        ];
    }

    public function get(int $id): PppoeAccount
    {
        $account = $this->accounts->find($id) ?? throw new NotFoundException('PPPoE account not found.');
        return $this->enrichAccountInfo($account);
    }

    public function create(CreatePppoeAccountData $data, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount
    {
        $this->validator->validateCreate($data);

        if ($this->accounts->existsByUsername($data->username)) {
            throw new ValidationException([[
                'code' => 'unique',
                'detail' => 'A PPPoE account with this username already exists.',
                'source' => ['pointer' => '/data/attributes/username'],
            ]]);
        }

        $customer = $this->customers->find($data->customerId) ?? throw new ValidationException([[
            'code' => 'not_found',
            'detail' => 'Selected customer does not exist.',
            'source' => ['pointer' => '/data/attributes/customer_id'],
        ]]);

        $connection = $this->connections->find($data->connectionId) ?? throw new ValidationException([[
            'code' => 'not_found',
            'detail' => 'Selected connection does not exist.',
            'source' => ['pointer' => '/data/attributes/connection_id'],
        ]]);

        $package = $this->packages->find($data->packageId) ?? throw new ValidationException([[
            'code' => 'not_found',
            'detail' => 'Selected internet package does not exist.',
            'source' => ['pointer' => '/data/attributes/package_id'],
        ]]);

        $router = $this->routerService->get($data->routerId);

        $encryptedPassword = $this->cipher->encrypt($data->password);

        $insertPayload = [
            'username' => $data->username,
            'password_encrypted' => $encryptedPassword,
            'customer_id' => $data->customerId,
            'connection_id' => $data->connectionId,
            'package_id' => $data->packageId,
            'router_id' => $data->routerId,
            'profile' => $data->profile,
            'service' => $data->service,
            'ip_pool' => $data->ipPool,
            'static_ip' => $data->staticIp,
            'mac_binding' => $data->macBinding,
            'caller_id' => $data->callerId,
            'rate_limit' => $data->rateLimit,
            'session_timeout' => $data->sessionTimeout,
            'idle_timeout' => $data->idleTimeout,
            'shared_users' => $data->sharedUsers,
            'status' => $data->status,
            'sync_status' => 'out_of_sync',
            'notes' => $data->notes,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ];

        $account = $this->accounts->insert($insertPayload);

        // Push to MikroTik router
        try {
            $this->pushSecretToRouter($account, $data->password);
            $this->accounts->updateSyncStatus($account->id(), 'synced');
            $account = $this->accounts->find($account->id()) ?? $account;
            $this->syncLogger->log($data->routerId, $account->id(), 'sync_user', 'success', "Secret '{$data->username}' provisioned on router.", null, $actorId);
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->accounts->updateSyncStatus($account->id(), 'out_of_sync');
            $account = $this->accounts->find($account->id()) ?? $account;
            $this->syncLogger->log($data->routerId, $account->id(), 'sync_user', 'failed', "Failed to provision secret: " . $e->getMessage(), null, $actorId);
        }

        $this->auditLogger->log($actorId, 'create', 'pppoe_account', $account->id(), null, $account->toArray(), $ip, $userAgent);

        return $this->enrichAccountInfo($account);
    }

    public function update(int $id, UpdatePppoeAccountData $data, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount
    {
        $existing = $this->accounts->find($id) ?? throw new NotFoundException('PPPoE account not found.');
        $this->validator->validateUpdate($data);

        if ($data->username !== null && $data->username !== $existing->username()) {
            if ($this->accounts->existsByUsername($data->username, $id)) {
                throw new ValidationException([[
                    'code' => 'unique',
                    'detail' => 'A PPPoE account with this username already exists.',
                    'source' => ['pointer' => '/data/attributes/username'],
                ]]);
            }
        }

        $updatePayload = ['updated_by' => $actorId];
        $passwordPlain = null;

        if ($data->username !== null) {
            $updatePayload['username'] = $data->username;
        }
        if ($data->password !== null) {
            $passwordPlain = $data->password;
            $updatePayload['password_encrypted'] = $this->cipher->encrypt($data->password);
        }
        if ($data->packageId !== null) {
            $this->packages->find($data->packageId) ?? throw new ValidationException([[
                'code' => 'not_found',
                'detail' => 'Selected internet package does not exist.',
                'source' => ['pointer' => '/data/attributes/package_id'],
            ]]);
            $updatePayload['package_id'] = $data->packageId;
        }
        if ($data->routerId !== null) {
            $this->routerService->get($data->routerId);
            $updatePayload['router_id'] = $data->routerId;
        }
        if ($data->profile !== null) {
            $updatePayload['profile'] = $data->profile;
        }
        if ($data->service !== null) {
            $updatePayload['service'] = $data->service;
        }
        if (property_exists($data, 'ipPool')) {
            $updatePayload['ip_pool'] = $data->ipPool;
        }
        if (property_exists($data, 'staticIp')) {
            $updatePayload['static_ip'] = $data->staticIp;
        }
        if (property_exists($data, 'macBinding')) {
            $updatePayload['mac_binding'] = $data->macBinding;
        }
        if (property_exists($data, 'callerId')) {
            $updatePayload['caller_id'] = $data->callerId;
        }
        if (property_exists($data, 'rateLimit')) {
            $updatePayload['rate_limit'] = $data->rateLimit;
        }
        if (property_exists($data, 'sessionTimeout')) {
            $updatePayload['session_timeout'] = $data->sessionTimeout;
        }
        if (property_exists($data, 'idleTimeout')) {
            $updatePayload['idle_timeout'] = $data->idleTimeout;
        }
        if ($data->sharedUsers !== null) {
            $updatePayload['shared_users'] = $data->sharedUsers;
        }
        if ($data->status !== null) {
            $updatePayload['status'] = $data->status;
        }
        if (property_exists($data, 'notes')) {
            $updatePayload['notes'] = $data->notes;
        }

        $updated = $this->accounts->update($id, $updatePayload);

        // Push update to router
        try {
            if ($passwordPlain === null) {
                try {
                    $passwordPlain = $this->cipher->decrypt($updated->encryptedPassword());
                } catch (\Throwable) {
                    $passwordPlain = null;
                }
            }
            $this->updateSecretOnRouter($existing->username(), $updated, $passwordPlain);
            $this->accounts->updateSyncStatus($updated->id(), 'synced');
            $updated = $this->accounts->find($id) ?? $updated;
            $this->syncLogger->log($updated->routerId(), $updated->id(), 'sync_user', 'success', "Secret '{$updated->username()}' updated on router.", null, $actorId);
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->accounts->updateSyncStatus($updated->id(), 'out_of_sync');
            $updated = $this->accounts->find($id) ?? $updated;
            $this->syncLogger->log($updated->routerId(), $updated->id(), 'sync_user', 'failed', "Failed to update secret on router: " . $e->getMessage(), null, $actorId);
        }

        $this->auditLogger->log($actorId, 'update', 'pppoe_account', $id, $existing->toArray(), $updated->toArray(), $ip, $userAgent);

        return $this->enrichAccountInfo($updated);
    }

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $existing = $this->accounts->find($id) ?? throw new NotFoundException('PPPoE account not found.');

        // Remove from MikroTik router
        try {
            $this->removeSecretFromRouter($existing);
            $this->syncLogger->log($existing->routerId(), $id, 'sync_user', 'success', "Secret '{$existing->username()}' removed from router.", null, $actorId);
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->syncLogger->log($existing->routerId(), $id, 'sync_user', 'warning', "Soft deleted in DB, but failed to remove secret from router: " . $e->getMessage(), null, $actorId);
        }

        $this->accounts->delete($id);
        $this->auditLogger->log($actorId, 'delete', 'pppoe_account', $id, $existing->toArray(), null, $ip, $userAgent);
    }

    public function setEnabled(int $id, bool $isEnabled, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount
    {
        return $this->update($id, new UpdatePppoeAccountData(status: $isEnabled ? 'active' : 'disabled'), $actorId, $ip, $userAgent);
    }

    public function suspend(int $id, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount
    {
        $account = $this->update($id, new UpdatePppoeAccountData(status: 'suspended'), $actorId, $ip, $userAgent);
        $this->reconnect($id, $actorId, $ip, $userAgent); // Disconnect live session if any
        return $account;
    }

    public function resume(int $id, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount
    {
        return $this->update($id, new UpdatePppoeAccountData(status: 'active'), $actorId, $ip, $userAgent);
    }

    public function reconnect(int $id, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $account = $this->accounts->find($id) ?? throw new NotFoundException('PPPoE account not found.');
        $connection = $this->routerService->connectionData($account->routerId());

        try {
            $responses = $this->pool->executeBatch($connection, [
                ['/ppp/active/print', '?name=' . $account->username()]
            ]);
            $rows = $responses[0] ?? [];
            foreach ($rows as $row) {
                $activeId = $row['.id'] ?? null;
                if ($activeId !== null) {
                    $this->pool->executeBatch($connection, [
                        ['/ppp/active/remove', '=.id=' . $activeId]
                    ]);
                }
            }
            $this->syncLogger->log($account->routerId(), $id, 'sync_user', 'success', "Force disconnected active session for '{$account->username()}'.", null, $actorId);
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->syncLogger->log($account->routerId(), $id, 'sync_user', 'failed', "Failed to force disconnect session: " . $e->getMessage(), null, $actorId);
        }

        $this->auditLogger->log($actorId, 'reconnect', 'pppoe_account', $id, null, ['reconnected' => true], $ip, $userAgent);
    }

    public function resetPassword(int $id, string $newPassword, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount
    {
        if (strlen($newPassword) < 6) {
            throw new ValidationException([[
                'code' => 'invalid_value',
                'detail' => 'PPPoE password must be at least 6 characters.',
                'source' => ['pointer' => '/data/attributes/password'],
            ]]);
        }

        return $this->update($id, new UpdatePppoeAccountData(password: $newPassword), $actorId, $ip, $userAgent);
    }

    public function changePackage(int $id, int $newPackageId, ?string $newProfile, int $actorId, ?string $ip, ?string $userAgent): PppoeAccount
    {
        $package = $this->packages->find($newPackageId) ?? throw new ValidationException([[
            'code' => 'not_found',
            'detail' => 'Selected internet package does not exist.',
            'source' => ['pointer' => '/data/attributes/package_id'],
        ]]);

        $profileName = $newProfile;
        if ($profileName === null || trim($profileName) === '') {
            // Attempt to derive or use package name / default
            $pkgArray = $package->toArray();
            $profileName = isset($pkgArray['name']) && is_string($pkgArray['name']) ? trim($pkgArray['name']) : 'default';
        }

        return $this->update($id, new UpdatePppoeAccountData(packageId: $newPackageId, profile: $profileName), $actorId, $ip, $userAgent);
    }

    private function pushSecretToRouter(PppoeAccount $account, string $passwordPlain): void
    {
        $connection = $this->routerService->connectionData($account->routerId());

        // Check if exists first
        $responses = $this->pool->executeBatch($connection, [
            ['/ppp/secret/print', '?name=' . $account->username()]
        ]);
        $rows = $responses[0] ?? [];
        $existingId = $rows[0]['.id'] ?? null;

        $isDisabled = $account->status() !== 'active' ? 'yes' : 'no';

        if ($existingId !== null) {
            $sentence = [
                '/ppp/secret/set',
                '=.id=' . $existingId,
                '=password=' . $passwordPlain,
                '=service=' . $account->service(),
                '=profile=' . $account->profile(),
                '=disabled=' . $isDisabled,
            ];
            $this->appendOptionalAttributes($sentence, $account);
            $this->pool->executeBatch($connection, [$sentence]);
        } else {
            $sentence = [
                '/ppp/secret/add',
                '=name=' . $account->username(),
                '=password=' . $passwordPlain,
                '=service=' . $account->service(),
                '=profile=' . $account->profile(),
                '=disabled=' . $isDisabled,
                '=comment=SkyFi:Cust#' . $account->customerId() . ':Conn#' . $account->connectionId(),
            ];
            $this->appendOptionalAttributes($sentence, $account);
            $this->pool->executeBatch($connection, [$sentence]);
        }
    }

    private function updateSecretOnRouter(string $oldUsername, PppoeAccount $account, ?string $passwordPlain): void
    {
        $connection = $this->routerService->connectionData($account->routerId());

        $responses = $this->pool->executeBatch($connection, [
            ['/ppp/secret/print', '?name=' . $oldUsername]
        ]);
        $rows = $responses[0] ?? [];
        $existingId = $rows[0]['.id'] ?? null;

        $isDisabled = $account->status() !== 'active' ? 'yes' : 'no';

        if ($existingId !== null) {
            $sentence = [
                '/ppp/secret/set',
                '=.id=' . $existingId,
                '=name=' . $account->username(),
                '=service=' . $account->service(),
                '=profile=' . $account->profile(),
                '=disabled=' . $isDisabled,
            ];
            if ($passwordPlain !== null) {
                $sentence[] = '=password=' . $passwordPlain;
            }
            $this->appendOptionalAttributes($sentence, $account);
            $this->pool->executeBatch($connection, [$sentence]);
        } else {
            // If secret was deleted or missing on router, re-create if we have password
            if ($passwordPlain !== null) {
                $this->pushSecretToRouter($account, $passwordPlain);
            } else {
                throw new MikrotikCommandException('Secret not found on router and no plaintext password available to re-create it.');
            }
        }
    }

    private function removeSecretFromRouter(PppoeAccount $account): void
    {
        $connection = $this->routerService->connectionData($account->routerId());

        $responses = $this->pool->executeBatch($connection, [
            ['/ppp/secret/print', '?name=' . $account->username()]
        ]);
        $rows = $responses[0] ?? [];
        $existingId = $rows[0]['.id'] ?? null;

        if ($existingId !== null) {
            $this->pool->executeBatch($connection, [
                ['/ppp/secret/remove', '=.id=' . $existingId]
            ]);
        }

        // Also terminate active session if any
        try {
            $activeResponses = $this->pool->executeBatch($connection, [
                ['/ppp/active/print', '?name=' . $account->username()]
            ]);
            $activeRows = $activeResponses[0] ?? [];
            foreach ($activeRows as $ar) {
                if (isset($ar['.id'])) {
                    $this->pool->executeBatch($connection, [
                        ['/ppp/active/remove', '=.id=' . $ar['.id']]
                    ]);
                }
            }
        } catch (\Throwable) {
            // Ignore active session removal error on delete
        }
    }

    /** @param array<int, string> $sentence */
    private function appendOptionalAttributes(array &$sentence, PppoeAccount $account): void
    {
        if ($account->staticIp() !== null) {
            $sentence[] = '=remote-address=' . $account->staticIp();
        } else {
            $sentence[] = '=remote-address=';
        }

        if ($account->callerId() !== null) {
            $sentence[] = '=caller-id=' . $account->callerId();
        } elseif ($account->macBinding() !== null) {
            $sentence[] = '=caller-id=' . $account->macBinding();
        } else {
            $sentence[] = '=caller-id=';
        }

        if ($account->rateLimit() !== null) {
            $sentence[] = '=limit-bytes-out=' . $account->rateLimit(); // Or RouterOS queue/rate attributes
        }
    }

    private function enrichAccountInfo(PppoeAccount $account): PppoeAccount
    {
        $attributes = $account->toArray();

        // Attach router name
        try {
            $router = $this->routerService->get($account->routerId());
            $attributes['router_name'] = $router->toArray()['name'] ?? 'Unknown Router';
        } catch (\Throwable) {
            $attributes['router_name'] = 'Router #' . $account->routerId();
        }

        // Attach customer name
        try {
            $customer = $this->customers->find($account->customerId());
            $attributes['customer_name'] = $customer ? ($customer->toArray()['full_name'] ?? $customer->toArray()['name'] ?? 'Customer #' . $account->customerId()) : 'Customer #' . $account->customerId();
        } catch (\Throwable) {
            $attributes['customer_name'] = 'Customer #' . $account->customerId();
        }

        // Attach connection number
        try {
            $connection = $this->connections->find($account->connectionId());
            $attributes['connection_number'] = $connection ? ($connection->toArray()['connection_number'] ?? 'Conn #' . $account->connectionId()) : 'Conn #' . $account->connectionId();
        } catch (\Throwable) {
            $attributes['connection_number'] = 'Conn #' . $account->connectionId();
        }

        // Attach package name
        try {
            $package = $this->packages->find($account->packageId());
            $attributes['package_name'] = $package ? ($package->toArray()['name'] ?? 'Package #' . $account->packageId()) : 'Package #' . $account->packageId();
        } catch (\Throwable) {
            $attributes['package_name'] = 'Package #' . $account->packageId();
        }

        $attributes['password_encrypted'] = $account->encryptedPassword();
        return PppoeAccount::fromRow($attributes);
    }
}
