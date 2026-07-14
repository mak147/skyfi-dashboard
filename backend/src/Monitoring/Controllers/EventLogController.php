<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Controllers;

use SkyFi\Monitoring\Contracts\EventLoggingServiceContract;
use SkyFi\Monitoring\DTOs\EventLogListFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class EventLogController
{
    public function __construct(
        private readonly EventLoggingServiceContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function listMonitoringEvents(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $filters = EventLogListFilters::fromRequest($request->queryParams());
        $result = $this->service->listMonitoringEvents($filters);

        $items = array_map(static fn ($e) => $e->toArray(), $result['items']);

        return new Response(200, [
            'data' => [
                'items' => $items,
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
            ],
        ]);
    }

    public function listSyncEvents(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $params = $request->queryParams();
        $page = isset($params['page']) && (int) $params['page'] > 0 ? (int) $params['page'] : 1;
        $perPage = isset($params['per_page']) && (int) $params['per_page'] > 0 ? (int) $params['per_page'] : 25;
        $routerId = isset($params['router_id']) && (int) $params['router_id'] > 0 ? (int) $params['router_id'] : null;
        $syncType = isset($params['sync_type']) && $params['sync_type'] !== '' ? (string) $params['sync_type'] : null;

        $result = $this->service->listSyncEvents($page, $perPage, $routerId, $syncType);

        $items = array_map(static fn ($e) => $e->toArray(), $result['items']);

        return new Response(200, [
            'data' => [
                'items' => $items,
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
            ],
        ]);
    }
}
