<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Controllers;

use SkyFi\Monitoring\Contracts\DeviceHealthPollingServiceContract;
use SkyFi\Monitoring\Contracts\InterfaceSnapshotRepositoryContract;
use SkyFi\Monitoring\Contracts\MonitoringDashboardServiceContract;
use SkyFi\Monitoring\DTOs\InterfaceMetricsFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class DeviceHealthController
{
    public function __construct(
        private readonly MonitoringDashboardServiceContract $dashboardService,
        private readonly DeviceHealthPollingServiceContract $pollingService,
        private readonly InterfaceSnapshotRepositoryContract $ifaceRepo,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function listDeviceHealth(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $params = $request->queryParams();
        $page = isset($params['page']) && (int) $params['page'] > 0 ? (int) $params['page'] : 1;
        $perPage = isset($params['per_page']) && (int) $params['per_page'] > 0 ? (int) $params['per_page'] : 20;
        $deviceType = isset($params['device_type']) && $params['device_type'] !== '' ? (string) $params['device_type'] : null;
        $status = isset($params['status']) && $params['status'] !== '' ? (string) $params['status'] : null;

        $payload = $this->dashboardService->getDeviceHealthList($page, $perPage, $deviceType, $status);

        return new Response(200, ['data' => $payload]);
    }

    public function pollRouter(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.check');

        $id = (int) ($request->attributes()['route_params']['id'] ?? 0);

        $result = $this->pollingService->pollRouterHealth($id, $userId, $request->ipAddress(), $request->header('User-Agent'));

        return new Response(200, ['data' => $result]);
    }

    public function pollAll(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.check');

        $result = $this->pollingService->pollAllDevices($userId, $request->ipAddress(), $request->header('User-Agent'));

        return new Response(200, ['data' => $result]);
    }

    public function listInterfaces(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $filters = InterfaceMetricsFilters::fromRequest($request->queryParams());
        $result = $this->ifaceRepo->listSnapshots($filters);

        $items = array_map(static fn ($s) => $s->toArray(), $result['items']);

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
