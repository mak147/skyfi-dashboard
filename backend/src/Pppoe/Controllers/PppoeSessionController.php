<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Controllers;

use SkyFi\Pppoe\Services\PppoeSessionMonitorService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class PppoeSessionController
{
    public function __construct(
        private readonly PppoeSessionMonitorService $monitor,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function activeSessions(Request $request): Response
    {
        $this->authorize($request, 'pppoe.monitor');
        $query = $request->query();
        $routerId = isset($query['router_id']) && is_numeric($query['router_id']) ? (int) $query['router_id'] : null;

        $sessions = $this->monitor->listActiveSessions($routerId);

        return new Response(200, [
            'data' => array_map(static fn ($s): array => [
                'type' => 'pppoe-active-sessions',
                'id' => $s->id(),
                'attributes' => $s->toArray(),
            ], $sessions),
        ]);
    }

    public function disconnectSession(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.manage');
        $body = $request->body();
        $routerId = (int) ($body['router_id'] ?? 0);
        $sessionIdOrUsername = (string) ($body['session_id'] ?? $body['username'] ?? '');

        $this->monitor->disconnectSession($routerId, $sessionIdOrUsername, $actorId, $request->ipAddress(), $request->userAgent());

        return new Response(200, ['data' => ['message' => 'Active session disconnected successfully.']]);
    }

    public function sessionHistory(Request $request): Response
    {
        $this->authorize($request, 'pppoe.monitor');
        $query = $request->query();
        $page = isset($query['page']) && is_array($query['page']) ? max(1, (int) ($query['page']['number'] ?? 1)) : max(1, (int) ($query['page'] ?? 1));
        $perPage = isset($query['page']) && is_array($query['page']) ? max(1, min(100, (int) ($query['page']['size'] ?? 15))) : 15;

        $filter = isset($query['filter']) && is_array($query['filter']) ? $query['filter'] : $query;
        $accountId = isset($filter['account_id']) && is_numeric($filter['account_id']) ? (int) $filter['account_id'] : null;
        if ($accountId === null && isset($request->attributes()['route_params']['id'])) {
            $accountId = (int) $request->attributes()['route_params']['id'];
        }
        $routerId = isset($filter['router_id']) && is_numeric($filter['router_id']) ? (int) $filter['router_id'] : null;
        $username = isset($filter['username']) && $filter['username'] !== '' ? trim((string) $filter['username']) : null;

        $result = $this->monitor->listSessionHistory($page, $perPage, $accountId, $routerId, $username);

        return new Response(200, [
            'data' => array_map(static fn ($h): array => [
                'type' => 'pppoe-session-history',
                'id' => (string) $h->id(),
                'attributes' => $h->toArray(),
            ], $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function statistics(Request $request): Response
    {
        $this->authorize($request, 'pppoe.monitor');
        $accountId = (int) (($request->attributes()['route_params'] ?? [])['id'] ?? 0);
        $stats = $this->monitor->getAccountStatistics($accountId);

        return new Response(200, [
            'data' => [
                'type' => 'pppoe-statistics',
                'id' => (string) $accountId,
                'attributes' => $stats,
            ],
        ]);
    }

    private function authorize(Request $request, string $permission): int
    {
        $claims = $request->attributes()['claims'] ?? [];
        $userId = isset($claims['sub']) ? (int) $claims['sub'] : 0;
        $this->authorizer->authorize($userId, $permission);

        return $userId;
    }
}
