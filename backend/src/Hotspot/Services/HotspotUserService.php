<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Services;

use SkyFi\Customers\Contracts\CustomerRepositoryContract;
use SkyFi\Hotspot\Contracts\HotspotProfileRepositoryContract;
use SkyFi\Hotspot\Contracts\HotspotSyncLoggerContract;
use SkyFi\Hotspot\Contracts\HotspotUserRepositoryContract;
use SkyFi\Hotspot\Contracts\HotspotUserServiceContract;
use SkyFi\Hotspot\DomainModels\HotspotUser;
use SkyFi\Hotspot\DTOs\BulkImportUserData;
use SkyFi\Hotspot\DTOs\CreateHotspotUserData;
use SkyFi\Hotspot\DTOs\HotspotUserListFilters;
use SkyFi\Hotspot\DTOs\UpdateHotspotUserData;
use SkyFi\Hotspot\Validators\HotspotUserValidator;
use SkyFi\Mikrotik\Contracts\CredentialCipherContract;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class HotspotUserService implements HotspotUserServiceContract
{
    public function __construct(
        private readonly HotspotUserRepositoryContract $users,
        private readonly HotspotProfileRepositoryContract $profiles,
        private readonly CustomerRepositoryContract $customers,
        private readonly RouterServiceContract $routerService,
        private readonly MikrotikConnectionPoolContract $pool,
        private readonly CredentialCipherContract $cipher,
        private readonly HotspotSyncLoggerContract $syncLogger,
        private readonly HotspotUserValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(HotspotUserListFilters $filters): array
    {
        $result = $this->users->list($filters);

        $enrichedItems = [];
        foreach ($result['items'] as $user) {
            $enrichedItems[] = $this->enrichUserInfo($user);
        }

        return [
            ...$result,
            'items' => $enrichedItems,
        ];
    }

    public function get(int $id): HotspotUser
    {
        $user = $this->users->find($id) ?? throw new NotFoundException('Hotspot user not found.');
        return $this->enrichUserInfo($user);
    }

    public function create(CreateHotspotUserData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        $this->validator->validateCreate($data);

        if ($this->users->existsByUsername($data->username)) {
            throw new ValidationException([[
                'code' => 'unique',
                'detail' => 'A hotspot user with this username already exists.',
                'source' => ['pointer' => '/data/attributes/username'],
            ]]);
        }

        $this->routerService->get($data->routerId);

        if ($data->customerId !== null) {
            $this->customers->find($data->customerId) ?? throw new ValidationException([[
                'code' => 'not_found',
                'detail' => 'Selected customer does not exist.',
                'source' => ['pointer' => '/data/attributes/customer_id'],
            ]]);
        }

        if ($data->profileId !== null) {
            $profile = $this->profiles->find($data->profileId) ?? throw new ValidationException([[
                'code' => 'not_found',
                'detail' => 'Selected hotspot profile does not exist.',
                'source' => ['pointer' => '/data/attributes/profile_id'],
            ]]);
            $profileName = $profile->routerProfileName();
        } else {
            $profileName = $data->profileName;
        }

        $encryptedPassword = $this->cipher->encrypt($data->password);

        $insertPayload = [
            'username' => $data->username,
            'password_encrypted' => $encryptedPassword,
            'customer_id' => $data->customerId,
            'connection_id' => $data->connectionId,
            'package_id' => $data->packageId,
            'router_id' => $data->routerId,
            'profile_id' => $data->profileId,
            'profile_name' => $profileName,
            'limit_uptime' => $data->limitUptime,
            'limit_bytes_in' => $data->limitBytesIn,
            'limit_bytes_out' => $data->limitBytesOut,
            'limit_bytes_total' => $data->limitBytesTotal,
            'mac_address' => $data->macAddress,
            'status' => $data->status,
            'sync_status' => 'out_of_sync',
            'notes' => $data->notes,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ];

        $user = $this->users->insert($insertPayload);

        try {
            $this->pushUserToRouter($user, $data->password);
            $this->users->updateSyncStatus($user->id(), 'synced');
            $user = $this->users->find($user->id()) ?? $user;
            $this->syncLogger->log($data->routerId, $user->id(), 'sync_user', 'success', "Hotspot user '{$data->username}' provisioned on router.", null, $actorId);
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->users->updateSyncStatus($user->id(), 'out_of_sync');
            $user = $this->users->find($user->id()) ?? $user;
            $this->syncLogger->log($data->routerId, $user->id(), 'sync_user', 'failed', "Failed to provision hotspot user: " . $e->getMessage(), null, $actorId);
        }

        $this->auditLogger->log($actorId, 'create', 'hotspot_user', $user->id(), null, $user->toArray(), $ip, $userAgent);

        return $this->enrichUserInfo($user);
    }

    public function update(int $id, UpdateHotspotUserData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        $existing = $this->users->find($id) ?? throw new NotFoundException('Hotspot user not found.');
        $this->validator->validateUpdate($data);

        if ($data->username !== null && $data->username !== $existing->username()) {
            if ($this->users->existsByUsername($data->username, $id)) {
                throw new ValidationException([[
                    'code' => 'unique',
                    'detail' => 'A hotspot user with this username already exists.',
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
        if ($data->routerId !== null) {
            $this->routerService->get($data->routerId);
            $updatePayload['router_id'] = $data->routerId;
        }
        if ($data->profileId !== null) {
            $profile = $this->profiles->find($data->profileId);
            if ($profile !== null) {
                $updatePayload['profile_id'] = $data->profileId;
                $updatePayload['profile_name'] = $profile->routerProfileName();
            }
        }
        if ($data->profileName !== null) {
            $updatePayload['profile_name'] = $data->profileName;
        }
        if (property_exists($data, 'customerId')) {
            $updatePayload['customer_id'] = $data->customerId;
        }
        if (property_exists($data, 'connectionId')) {
            $updatePayload['connection_id'] = $data->connectionId;
        }
        if (property_exists($data, 'packageId')) {
            $updatePayload['package_id'] = $data->packageId;
        }
        if (property_exists($data, 'limitUptime')) {
            $updatePayload['limit_uptime'] = $data->limitUptime;
        }
        if (property_exists($data, 'limitBytesIn')) {
            $updatePayload['limit_bytes_in'] = $data->limitBytesIn;
        }
        if (property_exists($data, 'limitBytesOut')) {
            $updatePayload['limit_bytes_out'] = $data->limitBytesOut;
        }
        if (property_exists($data, 'limitBytesTotal')) {
            $updatePayload['limit_bytes_total'] = $data->limitBytesTotal;
        }
        if (property_exists($data, 'macAddress')) {
            $updatePayload['mac_address'] = $data->macAddress;
        }
        if ($data->status !== null) {
            $updatePayload['status'] = $data->status;
        }
        if (property_exists($data, 'notes')) {
            $updatePayload['notes'] = $data->notes;
        }

        $updated = $this->users->update($id, $updatePayload);

        try {
            if ($passwordPlain === null) {
                try {
                    $passwordPlain = $this->cipher->decrypt($updated->encryptedPassword());
                } catch (\Throwable) {
                    $passwordPlain = null;
                }
            }
            $this->updateUserOnRouter($existing->username(), $updated, $passwordPlain);
            $this->users->updateSyncStatus($updated->id(), 'synced');
            $updated = $this->users->find($id) ?? $updated;
            $this->syncLogger->log($updated->routerId(), $updated->id(), 'sync_user', 'success', "Hotspot user '{$updated->username()}' updated on router.", null, $actorId);
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->users->updateSyncStatus($updated->id(), 'out_of_sync');
            $updated = $this->users->find($id) ?? $updated;
            $this->syncLogger->log($updated->routerId(), $updated->id(), 'sync_user', 'failed', "Failed to update hotspot user on router: " . $e->getMessage(), null, $actorId);
        }

        $this->auditLogger->log($actorId, 'update', 'hotspot_user', $id, $existing->toArray(), $updated->toArray(), $ip, $userAgent);

        return $this->enrichUserInfo($updated);
    }

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $existing = $this->users->find($id) ?? throw new NotFoundException('Hotspot user not found.');

        try {
            $this->removeUserFromRouter($existing);
            $this->syncLogger->log($existing->routerId(), $id, 'sync_user', 'success', "Hotspot user '{$existing->username()}' removed from router.", null, $actorId);
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->syncLogger->log($existing->routerId(), $id, 'sync_user', 'warning', "Soft deleted in DB, but failed to remove from router: " . $e->getMessage(), null, $actorId);
        }

        $this->users->delete($id);
        $this->auditLogger->log($actorId, 'delete', 'hotspot_user', $id, $existing->toArray(), null, $ip, $userAgent);
    }

    public function setEnabled(int $id, bool $isEnabled, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        return $this->update($id, new UpdateHotspotUserData(status: $isEnabled ? 'active' : 'disabled'), $actorId, $ip, $userAgent);
    }

    public function suspend(int $id, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        $user = $this->update($id, new UpdateHotspotUserData(status: 'suspended'), $actorId, $ip, $userAgent);
        $this->disconnectActiveSessions($user, $actorId);
        return $user;
    }

    public function resume(int $id, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        return $this->update($id, new UpdateHotspotUserData(status: 'active'), $actorId, $ip, $userAgent);
    }

    public function resetPassword(int $id, string $newPassword, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        if (strlen($newPassword) < 4) {
            throw new ValidationException([[
                'code' => 'invalid_value',
                'detail' => 'Hotspot password must be at least 4 characters.',
                'source' => ['pointer' => '/data/attributes/password'],
            ]]);
        }

        return $this->update($id, new UpdateHotspotUserData(password: $newPassword), $actorId, $ip, $userAgent);
    }

    public function assignProfile(int $id, int $profileId, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        return $this->update($id, new UpdateHotspotUserData(profileId: $profileId), $actorId, $ip, $userAgent);
    }

    public function assignRouter(int $id, int $routerId, int $actorId, ?string $ip, ?string $userAgent): HotspotUser
    {
        return $this->update($id, new UpdateHotspotUserData(routerId: $routerId), $actorId, $ip, $userAgent);
    }

    public function bulkImport(BulkImportUserData $data, int $actorId, ?string $ip, ?string $userAgent): array
    {
        if ($data->routerId <= 0) {
            throw new ValidationException([[
                'code' => 'required',
                'detail' => 'A valid router ID is required.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ]]);
        }

        $this->routerService->get($data->routerId);

        $importedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($data->users as $userData) {
            $username = trim((string) ($userData['username'] ?? ''));
            if ($username === '') {
                $failedCount++;
                $errors[] = 'Skipped entry with empty username.';
                continue;
            }

            if ($this->users->existsByUsername($username)) {
                $failedCount++;
                $errors[] = "Username '{$username}' already exists.";
                continue;
            }

            $password = (string) ($userData['password'] ?? '');
            if ($password === '') {
                $password = bin2hex(random_bytes(4));
            }

            $profileName = trim((string) ($userData['profile_name'] ?? ''));
            if ($profileName === '') {
                $profileName = $data->defaultProfileName;
            }

            try {
                $encryptedPassword = $this->cipher->encrypt($password);

                $this->users->insert([
                    'username' => $username,
                    'password_encrypted' => $encryptedPassword,
                    'router_id' => $data->routerId,
                    'profile_name' => $profileName,
                    'limit_uptime' => $userData['limit_uptime'] ?? null,
                    'limit_bytes_total' => $userData['limit_bytes_total'] ?? null,
                    'mac_address' => $userData['mac_address'] ?? null,
                    'status' => $data->defaultStatus,
                    'sync_status' => 'out_of_sync',
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $importedCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[] = "Error importing '{$username}': " . $e->getMessage();
            }
        }

        $this->auditLogger->log($actorId, 'bulk_import', 'hotspot_user', null, null, [
            'imported' => $importedCount,
            'failed' => $failedCount,
        ], $ip, $userAgent);

        return [
            'imported_count' => $importedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    private function pushUserToRouter(HotspotUser $user, string $passwordPlain): void
    {
        $connection = $this->routerService->connectionData($user->routerId());

        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/user/print', '?name=' . $user->username()]
        ]);
        $rows = $responses[0] ?? [];
        $existingId = $rows[0]['.id'] ?? null;

        $isDisabled = $user->status() !== 'active' ? 'yes' : 'no';

        if ($existingId !== null) {
            $sentence = [
                '/ip/hotspot/user/set',
                '=.id=' . $existingId,
                '=password=' . $passwordPlain,
                '=profile=' . $user->profileName(),
                '=disabled=' . $isDisabled,
            ];
            $this->appendOptionalAttributes($sentence, $user);
            $this->pool->executeBatch($connection, [$sentence]);
        } else {
            $sentence = [
                '/ip/hotspot/user/add',
                '=name=' . $user->username(),
                '=password=' . $passwordPlain,
                '=profile=' . $user->profileName(),
                '=disabled=' . $isDisabled,
                '=comment=SkyFi:HS#' . $user->id(),
            ];
            $this->appendOptionalAttributes($sentence, $user);
            $this->pool->executeBatch($connection, [$sentence]);
        }
    }

    private function updateUserOnRouter(string $oldUsername, HotspotUser $user, ?string $passwordPlain): void
    {
        $connection = $this->routerService->connectionData($user->routerId());

        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/user/print', '?name=' . $oldUsername]
        ]);
        $rows = $responses[0] ?? [];
        $existingId = $rows[0]['.id'] ?? null;

        $isDisabled = $user->status() !== 'active' ? 'yes' : 'no';

        if ($existingId !== null) {
            $sentence = [
                '/ip/hotspot/user/set',
                '=.id=' . $existingId,
                '=name=' . $user->username(),
                '=profile=' . $user->profileName(),
                '=disabled=' . $isDisabled,
            ];
            if ($passwordPlain !== null) {
                $sentence[] = '=password=' . $passwordPlain;
            }
            $this->appendOptionalAttributes($sentence, $user);
            $this->pool->executeBatch($connection, [$sentence]);
        } else {
            if ($passwordPlain !== null) {
                $this->pushUserToRouter($user, $passwordPlain);
            } else {
                throw new MikrotikCommandException('Hotspot user not found on router and no plaintext password available to re-create it.');
            }
        }
    }

    private function removeUserFromRouter(HotspotUser $user): void
    {
        $connection = $this->routerService->connectionData($user->routerId());

        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/user/print', '?name=' . $user->username()]
        ]);
        $rows = $responses[0] ?? [];
        $existingId = $rows[0]['.id'] ?? null;

        if ($existingId !== null) {
            $this->pool->executeBatch($connection, [
                ['/ip/hotspot/user/remove', '=.id=' . $existingId]
            ]);
        }

        // Also terminate active sessions
        try {
            $activeResponses = $this->pool->executeBatch($connection, [
                ['/ip/hotspot/active/print', '?user=' . $user->username()]
            ]);
            $activeRows = $activeResponses[0] ?? [];
            foreach ($activeRows as $ar) {
                if (isset($ar['.id'])) {
                    $this->pool->executeBatch($connection, [
                        ['/ip/hotspot/active/remove', '=.id=' . $ar['.id']]
                    ]);
                }
            }
        } catch (\Throwable) {
            // Ignore active session removal error on delete
        }
    }

    private function disconnectActiveSessions(HotspotUser $user, int $actorId): void
    {
        try {
            $connection = $this->routerService->connectionData($user->routerId());
            $responses = $this->pool->executeBatch($connection, [
                ['/ip/hotspot/active/print', '?user=' . $user->username()]
            ]);
            $rows = $responses[0] ?? [];
            foreach ($rows as $row) {
                if (isset($row['.id'])) {
                    $this->pool->executeBatch($connection, [
                        ['/ip/hotspot/active/remove', '=.id=' . $row['.id']]
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $this->syncLogger->log($user->routerId(), $user->id(), 'sync_user', 'failed', "Failed to disconnect active sessions: " . $e->getMessage(), null, $actorId);
        }
    }

    /** @param array<int, string> $sentence */
    private function appendOptionalAttributes(array &$sentence, HotspotUser $user): void
    {
        if ($user->limitUptime() !== null) {
            $sentence[] = '=limit-uptime=' . $user->limitUptime();
        }
        if ($user->limitBytesIn() !== null) {
            $sentence[] = '=limit-bytes-in=' . (string) $user->limitBytesIn();
        }
        if ($user->limitBytesOut() !== null) {
            $sentence[] = '=limit-bytes-out=' . (string) $user->limitBytesOut();
        }
        if ($user->limitBytesTotal() !== null) {
            $sentence[] = '=limit-bytes-total=' . (string) $user->limitBytesTotal();
        }
        if ($user->macAddress() !== null) {
            $sentence[] = '=mac-address=' . $user->macAddress();
        }
    }

    private function enrichUserInfo(HotspotUser $user): HotspotUser
    {
        $attributes = $user->toArray();
        $raw = $user->rawAttributes(); // I'll add this method to the model

        if (!isset($raw['router_name'])) {
            try {
                $router = $this->routerService->get($user->routerId());
                $attributes['router_name'] = $router->toArray()['name'] ?? 'Unknown Router';
            } catch (\Throwable) {
                $attributes['router_name'] = 'Router #' . $user->routerId();
            }
        } else {
            $attributes['router_name'] = $raw['router_name'];
        }

        if (!isset($raw['customer_name'])) {
            if ($user->customerId() !== null) {
                try {
                    $customer = $this->customers->find($user->customerId());
                    $attributes['customer_name'] = $customer ? ($customer->toArray()['full_name'] ?? $customer->toArray()['name'] ?? 'Customer #' . $user->customerId()) : null;
                } catch (\Throwable) {
                    $attributes['customer_name'] = null;
                }
            } else {
                $attributes['customer_name'] = null;
            }
        } else {
            $attributes['customer_name'] = $raw['customer_name'];
        }

        $attributes['password_encrypted'] = $user->encryptedPassword();
        return HotspotUser::fromRow($attributes);
    }
}
