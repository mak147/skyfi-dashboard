<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Services;

use SkyFi\Hotspot\Contracts\HotspotSessionRepositoryContract;
use SkyFi\Hotspot\Contracts\HotspotUserRepositoryContract;
use SkyFi\Hotspot\DomainModels\HotspotActiveSession;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;

final class HotspotSessionMonitorService
{
    public function __construct(
        private readonly HotspotUserRepositoryContract $users,
        private readonly HotspotSessionRepositoryContract $sessions,
        private readonly RouterServiceContract $routerService,
        private readonly MikrotikConnectionPoolContract $pool,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    /** @return array<int, HotspotActiveSession> */
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
                    ['/ip/hotspot/active/print']
                ]);
                $rows = $responses[0] ?? [];
                foreach ($rows as $row) {
                    $row['router_id'] = $router->id();
                    $row['router_name'] = $router->toArray()['name'] ?? 'Router #' . $router->id();

                    $username = $row['user'] ?? $row['username'] ?? '';
                    if ($username !== '') {
                        $user = $this->users->findByUsername($username);
                        if ($user !== null) {
                            $row['hotspot_user_id'] = $user->id();
                        }
                    }

                    $activeSessions[] = new HotspotActiveSession($row);
                }
            } catch (MikrotikConnectionException | MikrotikCommandException | \Throwable) {
                // Continue querying remaining routers
            }
        }

        return $activeSessions;
    }

    public function disconnectSession(int $routerId, string $sessionId, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $this->routerService->get($routerId);
        $connection = $this->routerService->connectionData($routerId);

        if (str_starts_with($sessionId, '*')) {
            $this->pool->executeBatch($connection, [
                ['/ip/hotspot/active/remove', '=.id=' . $sessionId]
            ]);
        } else {
            $responses = $this->pool->executeBatch($connection, [
                ['/ip/hotspot/active/print', '?user=' . $sessionId]
            ]);
            $rows = $responses[0] ?? [];
            if ($rows === []) {
                try {
                    $this->pool->executeBatch($connection, [
                        ['/ip/hotspot/active/remove', '=.id=' . $sessionId]
                    ]);
                    return;
                } catch (\Throwable) {
                    throw new NotFoundException("Active session '{$sessionId}' not found on router.");
                }
            }
            foreach ($rows as $row) {
                if (isset($row['.id'])) {
                    $this->pool->executeBatch($connection, [
                        ['/ip/hotspot/active/remove', '=.id=' . $row['.id']]
                    ]);
                }
            }
        }

        $this->auditLogger->log($actorId, 'disconnect_session', 'hotspot_session', null, ['router_id' => $routerId, 'session' => $sessionId], null, $ip, $userAgent);
    }

    public function forceLogout(string $username, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $user = $this->users->findByUsername($username);
        $routerId = $user !== null ? $user->routerId() : null;

        if ($routerId === null) {
            // Try to disconnect from all routers
            $listResult = $this->routerService->list(new RouterListFilters(perPage: 100));
            foreach ($listResult['items'] as $router) {
                if (!$router->isEnabled()) {
                    continue;
                }
                try {
                    $this->disconnectFromRouter($router->id(), $username);
                } catch (\Throwable) {
                    // Continue
                }
            }
        } else {
            $this->disconnectFromRouter($routerId, $username);
        }

        $this->auditLogger->log($actorId, 'force_logout', 'hotspot_session', null, ['username' => $username], null, $ip, $userAgent);
    }

    private function disconnectFromRouter(int $routerId, string $username): void
    {
        $connection = $this->routerService->connectionData($routerId);
        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/active/print', '?user=' . $username]
        ]);
        $rows = $responses[0] ?? [];
        foreach ($rows as $row) {
            if (isset($row['.id'])) {
                $this->pool->executeBatch($connection, [
                    ['/ip/hotspot/active/remove', '=.id=' . $row['.id']]
                ]);
            }
        }
    }

    public function listSessionHistory(int $page = 1, int $perPage = 15, ?int $userId = null, ?int $routerId = null, ?string $username = null): array
    {
        return $this->sessions->listHistory($page, $perPage, $userId, $routerId, $username);
    }

    public function listLoginHistory(int $page = 1, int $perPage = 15, ?int $userId = null, ?int $routerId = null): array
    {
        return $this->sessions->listLoginHistory($page, $perPage, $userId, $routerId);
    }

    public function getUserStatistics(int $userId): array
    {
        return $this->sessions->getUserStatistics($userId);
    }
}
