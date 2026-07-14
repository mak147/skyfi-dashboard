<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Controllers;

use SkyFi\Monitoring\Contracts\MonitoringDashboardServiceContract;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class MonitoringDashboardController
{
    public function __construct(
        private readonly MonitoringDashboardServiceContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function overview(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $payload = $this->service->getOverview();

        return new Response(200, ['data' => $payload]);
    }

    public function routerDetailedMetrics(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $id = (int) ($request->attributes()['route_params']['id'] ?? 0);
        $payload = $this->service->getRouterDetailedMetrics($id);

        return new Response(200, ['data' => $payload]);
    }
}
