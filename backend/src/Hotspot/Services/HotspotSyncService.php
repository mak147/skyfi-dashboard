<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Services;

use SkyFi\Hotspot\Contracts\HotspotProfileRepositoryContract;
use SkyFi\Hotspot\Contracts\HotspotSyncLoggerContract;
use SkyFi\Hotspot\Contracts\HotspotUserRepositoryContract;
use SkyFi\Hotspot\DomainModels\HotspotSyncResult;
use SkyFi\Hotspot\DTOs\ImportHotspotUsersData;
use SkyFi\Hotspot\DTOs\SyncOptionsData;
use SkyFi\Mikrotik\Contracts\CredentialCipherContract;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class HotspotSyncService
{
    public function __construct(
        private readonly HotspotUserRepositoryContract $users,
        private readonly HotspotProfileRepositoryContract $profiles,
        private readonly RouterServiceContract $routerService,
        private readonly MikrotikConnectionPoolContract $pool,
        private readonly CredentialCipherContract $cipher,
        private readonly HotspotSyncLoggerContract $syncLogger,
    ) {
    }

    public function syncRouter(int $routerId, ?int $actorId = null): HotspotSyncResult
    {
        $router = $this->routerService->get($routerId);
        $connection = $this->routerService->connectionData($routerId);

        $dbUsers = $this->users->listByRouter($routerId);
        $dbByUsername = [];
        foreach ($dbUsers as $user) {
            $dbByUsername[$user->username()] = $user;
        }

        $routerUsers = [];
        try {
            $responses = $this->pool->executeBatch($connection, [
                ['/ip/hotspot/user/print']
            ]);
            foreach ($responses[0] ?? [] as $row) {
                $name = $row['name'] ?? null;
                if ($name !== null && $name !== '') {
                    $routerUsers[$name] = $row;
                }
            }
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->syncLogger->log($routerId, null, 'sync_router', 'failed', "Failed to fetch hotspot users from router: " . $e->getMessage(), null, $actorId);
            return new HotspotSyncResult([
                'router_id' => $routerId,
                'router_name' => $router->toArray()['name'] ?? 'Router #' . $routerId,
                'status' => 'failed',
                'total_users_in_db' => count($dbUsers),
                'total_users_on_router' => 0,
                'discrepancies' => [
                    ['type' => 'connection_error', 'message' => $e->getMessage()]
                ]
            ]);
        }

        $discrepancies = [];

        // Check DB users against Router users
        foreach ($dbByUsername as $username => $user) {
            if (!isset($routerUsers[$username])) {
                if ($user->status() !== 'disabled' && $user->status() !== 'error') {
                    $discrepancies[] = [
                        'type' => 'missing_on_router',
                        'user_id' => $user->id(),
                        'username' => $username,
                        'message' => "User '{$username}' exists in DB ({$user->status()}) but is missing on router.",
                    ];
                    $this->users->updateSyncStatus($user->id(), 'missing_on_router');
                }
            } else {
                $routerUser = $routerUsers[$username];
                $routerDisabled = ($routerUser['disabled'] ?? 'false') === 'true';
                $dbDisabled = $user->status() !== 'active';

                $routerProfile = trim($routerUser['profile'] ?? '');
                $dbProfile = trim($user->profileName());

                $mismatches = [];
                if ($routerDisabled !== $dbDisabled) {
                    $mismatches[] = "Status mismatch (DB status '{$user->status()}', router disabled=" . ($routerDisabled ? 'yes' : 'no') . ")";
                }
                if ($routerProfile !== '' && $dbProfile !== '' && strtolower($routerProfile) !== strtolower($dbProfile)) {
                    $mismatches[] = "Profile mismatch (DB profile '{$dbProfile}', router profile '{$routerProfile}')";
                }

                if ($mismatches !== []) {
                    $discrepancies[] = [
                        'type' => 'conflict',
                        'user_id' => $user->id(),
                        'username' => $username,
                        'message' => implode('; ', $mismatches),
                        'details' => $mismatches,
                    ];
                    $this->users->updateSyncStatus($user->id(), 'conflict');
                } else {
                    $this->users->updateSyncStatus($user->id(), 'synced');
                }
            }
        }

        // Check for orphans on Router
        foreach ($routerUsers as $username => $routerUser) {
            if (!isset($dbByUsername[$username])) {
                $discrepancies[] = [
                    'type' => 'orphan_on_router',
                    'username' => $username,
                    'profile' => $routerUser['profile'] ?? 'default',
                    'disabled' => ($routerUser['disabled'] ?? 'false') === 'true',
                    'message' => "Hotspot user '{$username}' exists on router but not in SkyFi database.",
                ];
            }
        }

        $status = $discrepancies === [] ? 'synced' : 'out_of_sync';

        $this->syncLogger->log(
            $routerId,
            null,
            'sync_router',
            $status === 'synced' ? 'success' : 'warning',
            "Router hotspot audit complete: " . count($discrepancies) . " discrepancies found.",
            ['discrepancies' => $discrepancies],
            $actorId
        );

        return new HotspotSyncResult([
            'router_id' => $routerId,
            'router_name' => $router->toArray()['name'] ?? 'Router #' . $routerId,
            'status' => $status,
            'total_users_in_db' => count($dbUsers),
            'total_users_on_router' => count($routerUsers),
            'discrepancies' => $discrepancies,
        ]);
    }

    public function syncUser(int $userId, ?int $actorId = null): HotspotSyncResult
    {
        $user = $this->users->find($userId) ?? throw new NotFoundException('Hotspot user not found.');
        return $this->syncRouter($user->routerId(), $actorId);
    }

    /** @return array<int, array<string, mixed>> */
    public function detectMissing(?int $routerId = null): array
    {
        $routers = [];
        if ($routerId !== null && $routerId > 0) {
            $routers[] = $this->routerService->get($routerId);
        } else {
            $list = $this->routerService->list(new RouterListFilters(perPage: 100));
            $routers = $list['items'];
        }

        $missing = [];
        foreach ($routers as $router) {
            if (!$router->isEnabled()) {
                continue;
            }
            $result = $this->syncRouter($router->id());
            foreach ($result->discrepancies() as $d) {
                if ($d['type'] === 'missing_on_router') {
                    $missing[] = [
                        'router_id' => $router->id(),
                        'router_name' => $result->routerName(),
                        'user_id' => $d['user_id'] ?? null,
                        'username' => $d['username'] ?? '',
                        'message' => $d['message'] ?? '',
                    ];
                }
            }
        }

        return $missing;
    }

    /** @return array{repaired_count: int, failed_count: int, logs: array<int, string>} */
    public function repair(SyncOptionsData $options, int $actorId): array
    {
        $routers = [];
        if ($options->routerId !== null && $options->routerId > 0) {
            $routers[] = $this->routerService->get($options->routerId);
        } else {
            $list = $this->routerService->list(new RouterListFilters(perPage: 100));
            $routers = $list['items'];
        }

        $repairedCount = 0;
        $failedCount = 0;
        $logs = [];

        foreach ($routers as $router) {
            if (!$router->isEnabled()) {
                continue;
            }

            $result = $this->syncRouter($router->id(), $actorId);
            $connection = $this->routerService->connectionData($router->id());

            foreach ($result->discrepancies() as $d) {
                if ($d['type'] === 'missing_on_router' || $d['type'] === 'conflict') {
                    $userId = $d['user_id'] ?? null;
                    if ($userId !== null) {
                        $user = $this->users->find((int) $userId);
                        if ($user !== null) {
                            try {
                                $passwordPlain = $this->cipher->decrypt($user->encryptedPassword());
                                $isDisabled = $user->status() !== 'active' ? 'yes' : 'no';

                                $responses = $this->pool->executeBatch($connection, [
                                    ['/ip/hotspot/user/print', '?name=' . $user->username()]
                                ]);
                                $rows = $responses[0] ?? [];
                                $existingId = $rows[0]['.id'] ?? null;

                                if ($existingId !== null) {
                                    $this->pool->executeBatch($connection, [
                                        [
                                            '/ip/hotspot/user/set',
                                            '=.id=' . $existingId,
                                            '=password=' . $passwordPlain,
                                            '=profile=' . $user->profileName(),
                                            '=disabled=' . $isDisabled,
                                        ]
                                    ]);
                                } else {
                                    $sentence = [
                                        '/ip/hotspot/user/add',
                                        '=name=' . $user->username(),
                                        '=password=' . $passwordPlain,
                                        '=profile=' . $user->profileName(),
                                        '=disabled=' . $isDisabled,
                                        '=comment=SkyFi:HS#' . $user->id(),
                                    ];
                                    if ($user->limitUptime() !== null) {
                                        $sentence[] = '=limit-uptime=' . $user->limitUptime();
                                    }
                                    if ($user->limitBytesTotal() !== null) {
                                        $sentence[] = '=limit-bytes-total=' . (string) $user->limitBytesTotal();
                                    }
                                    $this->pool->executeBatch($connection, [$sentence]);
                                }
                                $this->users->updateSyncStatus($user->id(), 'synced');
                                $repairedCount++;
                                $logs[] = "Successfully repaired user '{$user->username()}' on Router #{$router->id()}.";
                            } catch (\Throwable $e) {
                                $failedCount++;
                                $logs[] = "Failed to repair user '{$user->username()}': " . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }

        return [
            'repaired_count' => $repairedCount,
            'failed_count' => $failedCount,
            'logs' => $logs,
        ];
    }

    /** @return array{imported_count: int, failed_count: int, errors: array<int, string>} */
    public function importUsers(ImportHotspotUsersData $data, int $actorId): array
    {
        if ($data->routerId <= 0) {
            throw new ValidationException([[
                'code' => 'required',
                'detail' => 'A valid Router ID is required for import.',
                'source' => ['pointer' => '/data/attributes/router_id'],
            ]]);
        }

        $router = $this->routerService->get($data->routerId);
        $connection = $this->routerService->connectionData($data->routerId);

        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/user/print']
        ]);
        $routerUsers = $responses[0] ?? [];

        $importedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($routerUsers as $routerUser) {
            $username = $routerUser['name'] ?? null;
            if ($username === null || trim($username) === '') {
                continue;
            }

            if ($data->usernames !== [] && !in_array($username, $data->usernames, true)) {
                continue;
            }

            if ($this->users->existsByUsername($username)) {
                if (!$data->overwriteConflicts) {
                    $failedCount++;
                    $errors[] = "Username '{$username}' already exists in SkyFi database.";
                    continue;
                }
            }

            try {
                $passwordPlain = 'ChangeMe' . bin2hex(random_bytes(3));
                $encryptedPassword = $this->cipher->encrypt($passwordPlain);

                $profileName = trim($routerUser['profile'] ?? 'default');
                $disabled = ($routerUser['disabled'] ?? 'false') === 'true';

                if ($this->users->existsByUsername($username)) {
                    $existing = $this->users->findByUsername($username);
                    if ($existing !== null) {
                        $this->users->update($existing->id(), [
                            'profile_name' => $profileName,
                            'status' => $disabled ? 'disabled' : 'active',
                            'sync_status' => 'synced',
                            'updated_by' => $actorId,
                        ]);
                        $importedCount++;
                    }
                } else {
                    $this->users->insert([
                        'username' => $username,
                        'password_encrypted' => $encryptedPassword,
                        'customer_id' => $data->defaultCustomerId,
                        'connection_id' => $data->defaultConnectionId,
                        'package_id' => $data->defaultPackageId,
                        'router_id' => $router->id(),
                        'profile_name' => $profileName,
                        'status' => $disabled ? 'disabled' : 'active',
                        'sync_status' => 'synced',
                        'notes' => 'Imported from Router #' . $router->id(),
                        'created_by' => $actorId,
                        'updated_by' => $actorId,
                    ]);
                    $importedCount++;
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[] = "Error importing '{$username}': " . $e->getMessage();
            }
        }

        $this->syncLogger->log(
            $data->routerId,
            null,
            'import_users',
            $failedCount === 0 ? 'success' : 'warning',
            "Imported {$importedCount} users with {$failedCount} errors.",
            ['errors' => $errors],
            $actorId
        );

        return [
            'imported_count' => $importedCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function importProfiles(int $routerId, int $actorId): array
    {
        $router = $this->routerService->get($routerId);
        $connection = $this->routerService->connectionData($routerId);

        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/user/profile/print']
        ]);
        $routerProfiles = $responses[0] ?? [];

        $importedCount = 0;
        $skippedCount = 0;
        $imported = [];

        foreach ($routerProfiles as $row) {
            $name = $row['name'] ?? null;
            if ($name === null || trim($name) === '') {
                continue;
            }

            $existing = $this->profiles->findByRouterAndName($routerId, $name);
            if ($existing !== null) {
                $skippedCount++;
                continue;
            }

            try {
                $profile = $this->profiles->insert([
                    'name' => $name,
                    'router_id' => $routerId,
                    'router_profile_name' => $name,
                    'rate_limit_up' => null,
                    'rate_limit_down' => null,
                    'session_timeout' => isset($row['session-timeout']) && is_numeric($row['session-timeout']) ? (int) $row['session-timeout'] : null,
                    'idle_timeout' => isset($row['idle-timeout']) && is_numeric($row['idle-timeout']) ? (int) $row['idle-timeout'] : null,
                    'shared_users' => isset($row['shared-users']) && is_numeric($row['shared-users']) ? (int) $row['shared-users'] : 1,
                    'mac_cookie_timeout' => $row['mac-cookie-timeout'] ?? null,
                    'login_methods' => $row['login-by'] ?? 'http-pap',
                    'status' => 'active',
                    'sync_status' => 'synced',
                    'notes' => 'Imported from Router #' . $routerId,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
                $imported[] = $profile->toArray();
                $importedCount++;
            } catch (\Throwable) {
                $skippedCount++;
            }
        }

        $this->syncLogger->log(
            $routerId,
            null,
            'import_profiles',
            'success',
            "Imported {$importedCount} profiles, skipped {$skippedCount}.",
            null,
            $actorId
        );

        return $imported;
    }

    /** @return array<int, array<string, mixed>> */
    public function listRouterProfiles(int $routerId): array
    {
        $connection = $this->routerService->connectionData($routerId);

        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/user/profile/print']
        ]);
        $rows = $responses[0] ?? [];

        $profiles = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? null;
            if ($name !== null && $name !== '') {
                $profiles[] = [
                    'id' => $row['.id'] ?? null,
                    'name' => $name,
                    'rate_limit' => $row['rate-limit'] ?? null,
                    'session_timeout' => $row['session-timeout'] ?? null,
                    'idle_timeout' => $row['idle-timeout'] ?? null,
                    'shared_users' => $row['shared-users'] ?? null,
                    'mac_cookie_timeout' => $row['mac-cookie-timeout'] ?? null,
                    'login_by' => $row['login-by'] ?? null,
                ];
            }
        }

        return $profiles;
    }
}
