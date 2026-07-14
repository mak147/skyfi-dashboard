<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Controllers;

use SkyFi\Hotspot\Services\HotspotSessionMonitorService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class HotspotSessionController
{
    public function __construct(
        private readonly HotspotSessionMonitorService $monitor,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function activeSessions(Request $request): Response
    {
        $this->authorize($request, 'hotspot.monitor');
        $query = $request->query();
        $routerId = isset($query['router_id']) && is_numeric($query['router_id']) ? (int) $query['router_id'] : null;

        $sessions = $this->monitor->listActiveSessions($routerId);

        return new Response(200, [
            'data' => array_map(static fn ($s): array => [
                'type' => 'hotspot-active-sessions',
                'id' => $s->id(),
                'attributes' => $s->toArray(),
            ], $sessions),
        ]);
    }

    public function disconnectSession(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.monitor');
        $body = $request->body();
        $routerId = (int) ($body['router_id'] ?? 0);
        $sessionId = (string) ($body['session_id'] ?? $body['username'] ?? '');

        $this->monitor->disconnectSession($routerId, $sessionId, $actorId, $request->ipAddress(), $request->userAgent());

        return new Response(200, ['data' => ['message' => 'Active session disconnected successfully.']]);
    }

    public function forceLogout(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.monitor');
        $body = $request->body();
        $username = (string) ($body['username'] ?? '');

        $this->monitor->forceLogout($username, $actorId, $request->ipAddress(), $request->userAgent());

        return new Response(200, ['data' => ['message' => 'All active sessions for user force-disconnected.']]);
    }

    public function sessionHistory(Request $request): Response
    {
        $this->authorize($request, 'hotspot.monitor');
        $query = $request->query();
        $page = isset($query['page']) && is_array($query['page']) ? max(1, (int) ($query['page']['number'] ?? 1)) : max(1, (int) ($query['page'] ?? 1));
        $perPage = isset($query['page']) && is_array($query['page']) ? max(1, min(100, (int) ($query['page']['size'] ?? 15))) : 15;

        $filter = isset($query['filter']) && is_array($query['filter']) ? $query['filter'] : $query;
        $userId = isset($filter['user_id']) && is_numeric($filter['user_id']) ? (int) $filter['user_id'] : null;
        if ($userId === null && isset($request->attributes()['route_params']['id'])) {
            $userId = (int) $request->attributes()['route_params']['id'];
        }
        $routerId = isset($filter['router_id']) && is_numeric($filter['router_id']) ? (int) $filter['router_id'] : null;
        $username = isset($filter['username']) && $filter['username'] !== '' ? trim((string) $filter['username']) : null;

        $result = $this->monitor->listSessionHistory($page, $perPage, $userId, $routerId, $username);

        return new Response(200, [
            'data' => array_map(static fn ($h): array => [
                'type' => 'hotspot-session-history',
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

    public function userSessionHistory(Request $request): Response
    {
        $this->authorize($request, 'hotspot.monitor');
        $userId = (int) (($request->attributes()['route_params'] ?? [])['id'] ?? 0);
        $query = $request->query();
        $page = isset($query['page']) && is_array($query['page']) ? max(1, (int) ($query['page']['number'] ?? 1)) : max(1, (int) ($query['page'] ?? 1));
        $perPage = isset($query['page']) && is_array($query['page']) ? max(1, min(100, (int) ($query['page']['size'] ?? 15))) : 15;

        $result = $this->monitor->listSessionHistory($page, $perPage, $userId);

        return new Response(200, [
            'data' => array_map(static fn ($h): array => [
                'type' => 'hotspot-session-history',
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
        $this->authorize($request, 'hotspot.monitor');
        $userId = (int) (($request->attributes()['route_params'] ?? [])['id'] ?? 0);
        $stats = $this->monitor->getUserStatistics($userId);

        return new Response(200, [
            'data' => [
                'type' => 'hotspot-statistics',
                'id' => (string) $userId,
                'attributes' => $stats,
            ],
        ]);
    }

    public function loginHistory(Request $request): Response
    {
        $this->authorize($request, 'hotspot.monitor');
        $query = $request->query();
        $page = isset($query['page']) && is_array($query['page']) ? max(1, (int) ($query['page']['number'] ?? 1)) : max(1, (int) ($query['page'] ?? 1));
        $perPage = isset($query['page']) && is_array($query['page']) ? max(1, min(100, (int) ($query['page']['size'] ?? 15))) : 15;

        $filter = isset($query['filter']) && is_array($query['filter']) ? $query['filter'] : $query;
        $userId = isset($filter['user_id']) && is_numeric($filter['user_id']) ? (int) $filter['user_id'] : null;
        $routerId = isset($filter['router_id']) && is_numeric($filter['router_id']) ? (int) $filter['router_id'] : null;

        $result = $this->monitor->listLoginHistory($page, $perPage, $userId, $routerId);

        return new Response(200, [
            'data' => array_map(static fn ($h, $idx): array => [
                'type' => 'hotspot-login-history',
                'id' => (string) ($h['id'] ?? $idx + 1),
                'attributes' => $h,
            ], $result['items'], array_keys($result['items'])),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
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
