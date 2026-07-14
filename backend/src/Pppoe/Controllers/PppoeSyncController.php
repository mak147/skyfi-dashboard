<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Controllers;

use SkyFi\Pppoe\Contracts\PppoeSyncLoggerContract;
use SkyFi\Pppoe\DTOs\ImportPppoeUsersData;
use SkyFi\Pppoe\DTOs\SyncOptionsData;
use SkyFi\Pppoe\Services\PppoeSyncService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class PppoeSyncController
{
    public function __construct(
        private readonly PppoeSyncService $syncService,
        private readonly PppoeSyncLoggerContract $syncLogger,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function syncRouter(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.sync');
        $routerId = (int) (($request->attributes()['route_params'] ?? [])['routerId'] ?? 0);

        $result = $this->syncService->syncRouter($routerId, $actorId);

        return new Response(200, [
            'data' => [
                'type' => 'pppoe-sync-results',
                'id' => (string) $routerId,
                'attributes' => $result->toArray(),
            ],
        ]);
    }

    public function syncAccount(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.sync');
        $accountId = (int) (($request->attributes()['route_params'] ?? [])['id'] ?? 0);

        $result = $this->syncService->syncAccount($accountId, $actorId);

        return new Response(200, [
            'data' => [
                'type' => 'pppoe-sync-results',
                'id' => (string) $result->routerId(),
                'attributes' => $result->toArray(),
            ],
        ]);
    }

    public function detectMissing(Request $request): Response
    {
        $this->authorize($request, 'pppoe.sync');
        $query = $request->query();
        $routerId = isset($query['router_id']) && is_numeric($query['router_id']) ? (int) $query['router_id'] : null;

        $missing = $this->syncService->detectMissing($routerId);

        return new Response(200, [
            'data' => array_map(static fn ($m, $idx): array => [
                'type' => 'pppoe-missing-secrets',
                'id' => (string) ($idx + 1),
                'attributes' => $m,
            ], $missing, array_keys($missing)),
        ]);
    }

    public function repair(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.sync');
        $options = SyncOptionsData::fromArray($request->body());

        $result = $this->syncService->repair($options, $actorId);

        return new Response(200, [
            'data' => [
                'type' => 'pppoe-sync-repairs',
                'id' => 'repair-' . time(),
                'attributes' => $result,
            ],
        ]);
    }

    public function importUsers(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.sync');
        $data = ImportPppoeUsersData::fromArray($request->body());

        $result = $this->syncService->importUsers($data, $actorId);

        return new Response(200, [
            'data' => [
                'type' => 'pppoe-import-results',
                'id' => 'import-' . time(),
                'attributes' => $result,
            ],
        ]);
    }

    public function routerProfiles(Request $request): Response
    {
        $this->authorize($request, 'pppoe.view');
        $routerId = (int) (($request->attributes()['route_params'] ?? [])['routerId'] ?? 0);

        $profiles = $this->syncService->listRouterProfiles($routerId);

        return new Response(200, [
            'data' => array_map(static fn ($p, $idx): array => [
                'type' => 'mikrotik-ppp-profiles',
                'id' => (string) ($p['id'] ?? $idx + 1),
                'attributes' => $p,
            ], $profiles, array_keys($profiles)),
        ]);
    }

    public function syncLogs(Request $request): Response
    {
        $this->authorize($request, 'pppoe.sync');
        $query = $request->query();
        $limit = isset($query['limit']) && is_numeric($query['limit']) ? (int) $query['limit'] : 50;
        $routerId = isset($query['router_id']) && is_numeric($query['router_id']) ? (int) $query['router_id'] : null;
        $accountId = isset($query['account_id']) && is_numeric($query['account_id']) ? (int) $query['account_id'] : null;

        $logs = $this->syncLogger->listRecent($limit, $routerId, $accountId);

        return new Response(200, [
            'data' => array_map(static fn ($l): array => [
                'type' => 'pppoe-sync-logs',
                'id' => (string) ($l['id'] ?? '0'),
                'attributes' => $l,
            ], $logs),
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
