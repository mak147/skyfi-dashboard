<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Services;

use SkyFi\Connections\Contracts\ConnectionRepositoryContract;
use SkyFi\Customers\Contracts\CustomerRepositoryContract;
use SkyFi\Mikrotik\Contracts\CredentialCipherContract;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Packages\Contracts\PackageRepositoryContract;
use SkyFi\Pppoe\Contracts\PppoeAccountRepositoryContract;
use SkyFi\Pppoe\Contracts\PppoeSyncLoggerContract;
use SkyFi\Pppoe\DomainModels\PppoeSyncResult;
use SkyFi\Pppoe\DTOs\ImportPppoeUsersData;
use SkyFi\Pppoe\DTOs\SyncOptionsData;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class PppoeSyncService
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
    ) {
    }

    public function syncRouter(int $routerId, ?int $actorId = null): PppoeSyncResult
    {
        $router = $this->routerService->get($routerId);
        $connection = $this->routerService->connectionData($routerId);

        $dbAccounts = $this->accounts->listByRouter($routerId);
        $dbByUsername = [];
        foreach ($dbAccounts as $acc) {
            $dbByUsername[$acc->username()] = $acc;
        }

        $routerSecrets = [];
        try {
            $responses = $this->pool->executeBatch($connection, [
                ['/ppp/secret/print']
            ]);
            foreach ($responses[0] ?? [] as $row) {
                $name = $row['name'] ?? null;
                if ($name !== null && $name !== '') {
                    $routerSecrets[$name] = $row;
                }
            }
        } catch (MikrotikConnectionException | MikrotikCommandException $e) {
            $this->syncLogger->log($routerId, null, 'sync_router', 'failed', "Failed to fetch secrets from router: " . $e->getMessage(), null, $actorId);
            return new PppoeSyncResult([
                'router_id' => $routerId,
                'router_name' => $router->toArray()['name'] ?? 'Router #' . $routerId,
                'status' => 'failed',
                'total_accounts_in_db' => count($dbAccounts),
                'total_secrets_on_router' => 0,
                'discrepancies' => [
                    ['type' => 'connection_error', 'message' => $e->getMessage()]
                ]
            ]);
        }

        $discrepancies = [];

        // Check DB accounts against Router secrets
        foreach ($dbByUsername as $username => $account) {
            if (!isset($routerSecrets[$username])) {
                if ($account->status() !== 'disabled' && $account->status() !== 'error') {
                    $discrepancies[] = [
                        'type' => 'missing_on_router',
                        'account_id' => $account->id(),
                        'username' => $username,
                        'message' => "Account '{$username}' exists in DB ({$account->status()}) but is missing on router.",
                    ];
                    $this->accounts->updateSyncStatus($account->id(), 'missing_on_router');
                }
            } else {
                $secret = $routerSecrets[$username];
                $secretDisabled = ($secret['disabled'] ?? 'false') === 'true';
                $dbDisabled = $account->status() !== 'active';

                $secretProfile = trim($secret['profile'] ?? '');
                $dbProfile = trim($account->profile());

                $mismatches = [];
                if ($secretDisabled !== $dbDisabled) {
                    $mismatches[] = "Status mismatch (DB status '{$account->status()}', router disabled=" . ($secretDisabled ? 'yes' : 'no') . ")";
                }
                if ($secretProfile !== '' && $dbProfile !== '' && strtolower($secretProfile) !== strtolower($dbProfile)) {
                    $mismatches[] = "Profile mismatch (DB profile '{$dbProfile}', router profile '{$secretProfile}')";
                }

                if ($mismatches !== []) {
                    $discrepancies[] = [
                        'type' => 'conflict',
                        'account_id' => $account->id(),
                        'username' => $username,
                        'message' => implode('; ', $mismatches),
                        'details' => $mismatches,
                    ];
                    $this->accounts->updateSyncStatus($account->id(), 'conflict');
                } else {
                    $this->accounts->updateSyncStatus($account->id(), 'synced');
                }
            }
        }

        // Check for orphans on Router
        foreach ($routerSecrets as $username => $secret) {
            if (!isset($dbByUsername[$username])) {
                $discrepancies[] = [
                    'type' => 'orphan_on_router',
                    'username' => $username,
                    'profile' => $secret['profile'] ?? 'default',
                    'disabled' => ($secret['disabled'] ?? 'false') === 'true',
                    'message' => "Secret '{$username}' exists on router but not in SkyFi database.",
                ];
            }
        }

        $status = $discrepancies === [] ? 'synced' : 'out_of_sync';

        $this->syncLogger->log(
            $routerId,
            null,
            'sync_router',
            $status === 'synced' ? 'success' : 'warning',
            "Router audit complete: " . count($discrepancies) . " discrepancies found.",
            ['discrepancies' => $discrepancies],
            $actorId
        );

        return new PppoeSyncResult([
            'router_id' => $routerId,
            'router_name' => $router->toArray()['name'] ?? 'Router #' . $routerId,
            'status' => $status,
            'total_accounts_in_db' => count($dbAccounts),
            'total_secrets_on_router' => count($routerSecrets),
            'discrepancies' => $discrepancies,
        ]);
    }

    public function syncAccount(int $accountId, ?int $actorId = null): PppoeSyncResult
    {
        $account = $this->accounts->find($accountId) ?? throw new NotFoundException('PPPoE account not found.');
        return $this->syncRouter($account->routerId(), $actorId);
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
                        'account_id' => $d['account_id'] ?? null,
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
                    $accId = $d['account_id'] ?? null;
                    if ($accId !== null) {
                        $account = $this->accounts->find((int) $accId);
                        if ($account !== null) {
                            try {
                                $passwordPlain = $this->cipher->decrypt($account->encryptedPassword());
                                $isDisabled = $account->status() !== 'active' ? 'yes' : 'no';

                                // Check if secret exists right now
                                $responses = $this->pool->executeBatch($connection, [
                                    ['/ppp/secret/print', '?name=' . $account->username()]
                                ]);
                                $rows = $responses[0] ?? [];
                                $existingId = $rows[0]['.id'] ?? null;

                                if ($existingId !== null) {
                                    $this->pool->executeBatch($connection, [
                                        [
                                            '/ppp/secret/set',
                                            '=.id=' . $existingId,
                                            '=password=' . $passwordPlain,
                                            '=service=' . $account->service(),
                                            '=profile=' . $account->profile(),
                                            '=disabled=' . $isDisabled,
                                        ]
                                    ]);
                                } else {
                                    $this->pool->executeBatch($connection, [
                                        [
                                            '/ppp/secret/add',
                                            '=name=' . $account->username(),
                                            '=password=' . $passwordPlain,
                                            '=service=' . $account->service(),
                                            '=profile=' . $account->profile(),
                                            '=disabled=' . $isDisabled,
                                            '=comment=SkyFi:Cust#' . $account->customerId() . ':Conn#' . $account->connectionId(),
                                        ]
                                    ]);
                                }
                                $this->accounts->updateSyncStatus($account->id(), 'synced');
                                $repairedCount++;
                                $logs[] = "Successfully repaired account '{$account->username()}' on Router #{$router->id()}.";
                            } catch (\Throwable $e) {
                                $failedCount++;
                                $logs[] = "Failed to repair account '{$account->username()}': " . $e->getMessage();
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
    public function importUsers(ImportPppoeUsersData $data, int $actorId): array
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

        if ($data->defaultCustomerId !== null && $data->defaultCustomerId > 0) {
            $this->customers->find($data->defaultCustomerId) ?? throw new ValidationException([[
                'code' => 'not_found',
                'detail' => 'Default customer ID does not exist.',
                'source' => ['pointer' => '/data/attributes/default_customer_id'],
            ]]);
        }
        if ($data->defaultConnectionId !== null && $data->defaultConnectionId > 0) {
            $this->connections->find($data->defaultConnectionId) ?? throw new ValidationException([[
                'code' => 'not_found',
                'detail' => 'Default connection ID does not exist.',
                'source' => ['pointer' => '/data/attributes/default_connection_id'],
            ]]);
        }
        if ($data->defaultPackageId !== null && $data->defaultPackageId > 0) {
            $this->packages->find($data->defaultPackageId) ?? throw new ValidationException([[
                'code' => 'not_found',
                'detail' => 'Default package ID does not exist.',
                'source' => ['pointer' => '/data/attributes/default_package_id'],
            ]]);
        }

        $responses = $this->pool->executeBatch($connection, [
            ['/ppp/secret/print']
        ]);
        $routerSecrets = $responses[0] ?? [];

        $importedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($routerSecrets as $secret) {
            $username = $secret['name'] ?? null;
            if ($username === null || trim($username) === '') {
                continue;
            }

            if ($data->usernames !== [] && !in_array($username, $data->usernames, true)) {
                continue;
            }

            if ($this->accounts->existsByUsername($username)) {
                if (!$data->overwriteConflicts) {
                    $failedCount++;
                    $errors[] = "Username '{$username}' already exists in SkyFi database.";
                    continue;
                }
            }

            try {
                // RouterOS API read-only response doesn't return plaintext passwords for secrets.
                // If importing an existing secret, we assign a placeholder or encrypted secret if plaintext is provided
                $passwordPlain = $secret['password'] ?? 'ChangeMe' . bin2hex(random_bytes(3));
                $encryptedPassword = $this->cipher->encrypt($passwordPlain);

                $customerId = $data->defaultCustomerId ?? 1; // Default fallback if allowed
                $connectionId = $data->defaultConnectionId ?? 1;
                $packageId = $data->defaultPackageId ?? 1;
                $profile = trim($secret['profile'] ?? 'default');
                $disabled = ($secret['disabled'] ?? 'false') === 'true';

                if ($this->accounts->existsByUsername($username)) {
                    $existing = $this->accounts->findByUsername($username);
                    if ($existing !== null) {
                        $this->accounts->update($existing->id(), [
                            'profile' => $profile,
                            'status' => $disabled ? 'disabled' : 'active',
                            'sync_status' => 'synced',
                            'updated_by' => $actorId,
                        ]);
                        $importedCount++;
                    }
                } else {
                    $this->accounts->insert([
                        'username' => $username,
                        'password_encrypted' => $encryptedPassword,
                        'customer_id' => $customerId,
                        'connection_id' => $connectionId,
                        'package_id' => $packageId,
                        'router_id' => $router->id(),
                        'profile' => $profile,
                        'service' => $secret['service'] ?? 'pppoe',
                        'static_ip' => isset($secret['remote-address']) && $secret['remote-address'] !== '' ? $secret['remote-address'] : null,
                        'caller_id' => isset($secret['caller-id']) && $secret['caller-id'] !== '' ? $secret['caller-id'] : null,
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
    public function listRouterProfiles(int $routerId): array
    {
        $connection = $this->routerService->connectionData($routerId);

        $responses = $this->pool->executeBatch($connection, [
            ['/ppp/profile/print']
        ]);
        $rows = $responses[0] ?? [];

        $profiles = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? null;
            if ($name !== null && $name !== '') {
                $profiles[] = [
                    'id' => $row['.id'] ?? null,
                    'name' => $name,
                    'local_address' => $row['local-address'] ?? null,
                    'remote_address' => $row['remote-address'] ?? null,
                    'rate_limit' => $row['rate-limit'] ?? null,
                    'only_one' => $row['only-one'] ?? null,
                ];
            }
        }

        return $profiles;
    }
}
