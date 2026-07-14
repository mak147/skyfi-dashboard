<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Services;

use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Pppoe\Contracts\PppoeAccountRepositoryContract;
use SkyFi\Pppoe\Contracts\PppoeSessionRepositoryContract;
use SkyFi\Pppoe\DomainModels\PppoeActiveSession;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class PppoeSessionMonitorService
{
    public function __construct(
        private readonly PppoeAccountRepositoryContract $accounts,
        private readonly PppoeSessionRepositoryContract $sessions,
        private readonly RouterServiceContract $routerService,
        private readonly MikrotikConnectionPoolContract $pool,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    /** @return array<int, PppoeActiveSession> */
    public function listActiveSessions(?int $routerId = null): array
    {
        $routersToQuery = [];
        if ($routerId !== null && $routerId > 0) {
            try {
                $routersToQuery[] = $this->routerService->get($routerId);
            } catch (\Throwable) {
                return [];
            }
        } else {
            $listResult = $this->routerService->list(new RouterListFilters(perPage: 100));
            foreach ($listResult['items'] as $r) {
                if ($r->isEnabled()) {
                    $routersToQuery[] = $r;
                }
            }
        }

        $activeSessions = [];
        foreach ($routersToQuery as $router) {
            try {
                $connection = $this->routerService->connectionData($router->id());
                $responses = $this->pool->executeBatch($connection, [
                    ['/ppp/active/print', '?service=pppoe']
                ]);
                $rows = $responses[0] ?? [];
                foreach ($rows as $row) {
                    $row['router_id'] = $router->id();
                    $row['router_name'] = $router->toArray()['name'] ?? 'Router #' . $router->id();

                    // Enrich with DB account info if available
                    $username = $row['name'] ?? $row['username'] ?? '';
                    if ($username !== '') {
                        $account = $this->accounts->findByUsername($username);
                        if ($account !== null) {
                            $row['account_id'] = $account->id();
                            $row['customer_id'] = $account->customerId();
                            // If we already enriched account or we can attach basic ID
                        }
                    }

                    $activeSessions[] = new PppoeActiveSession($row);
                }
            } catch (MikrotikConnectionException | MikrotikCommandException | \Throwable) {
                // If a router is unreachable, continue querying remaining routers
            }
        }

        return $activeSessions;
    }

    public function disconnectSession(int $routerId, string $sessionIdOrUsername, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $router = $this->routerService->get($routerId);
        $connection = $this->routerService->connectionData($router->id());

        // Check if $sessionIdOrUsername starts with * or .id format, or query by name
        if (str_starts_with($sessionIdOrUsername, '*')) {
            $this->pool->executeBatch($connection, [
                ['/ppp/active/remove', '=.id=' . $sessionIdOrUsername]
            ]);
        } else {
            $responses = $this->pool->executeBatch($connection, [
                ['/ppp/active/print', '?name=' . $sessionIdOrUsername]
            ]);
            $rows = $responses[0] ?? [];
            if ($rows === []) {
                // Check if they passed an exact .id that doesn't start with *
                try {
                    $this->pool->executeBatch($connection, [
                        ['/ppp/active/remove', '=.id=' . $sessionIdOrUsername]
                    ]);
                    return;
                } catch (\Throwable) {
                    throw new NotFoundException("Active session '{$sessionIdOrUsername}' not found on router.");
                }
            }
            foreach ($rows as $row) {
                if (isset($row['.id'])) {
                    $this->pool->executeBatch($connection, [
                        ['/ppp/active/remove', '=.id=' . $row['.id']]
                    ]);
                }
            }
        }

        $this->auditLogger->log($actorId, 'disconnect_session', 'pppoe_session', null, ['router_id' => $routerId, 'session' => $sessionIdOrUsername], null, $ip, $userAgent);
    }

    public function listSessionHistory(
        int $page = 1,
        int $perPage = 15,
        ?int $accountId = null,
        ?int $routerId = null,
        ?string $username = null
    ): array {
        return $this->sessions->listHistory($page, $perPage, $accountId, $routerId, $username);
    }

    public function getAccountStatistics(int $accountId): array
    {
        return $this->sessions->getAccountStatistics($accountId);
    }
}
