<?php

declare(strict_types=1);

namespace SkyFi\Monitoring\Controllers;

use SkyFi\Monitoring\Contracts\AlertManagementServiceContract;
use SkyFi\Monitoring\DTOs\AlertListFilters;
use SkyFi\Monitoring\DTOs\CreateAlertData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class AlertController
{
    public function __construct(
        private readonly AlertManagementServiceContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function index(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $filters = AlertListFilters::fromRequest($request->queryParams());
        $result = $this->service->listAlerts($filters);

        $items = array_map(static fn ($a) => $a->toArray(), $result['items']);

        return new Response(200, [
            'data' => [
                'items' => $items,
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.view');

        $id = (int) ($request->attributes()['route_params']['id'] ?? 0);
        $result = $this->service->getAlert($id);

        return new Response(200, ['data' => $result]);
    }

    public function store(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.alerts');

        $payload = $request->parsedBody();
        $data = CreateAlertData::fromArray($payload);
        $alert = $this->service->createAlert($data);

        return new Response(201, ['data' => $alert->toArray()]);
    }

    public function acknowledge(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.alerts');

        $id = (int) ($request->attributes()['route_params']['id'] ?? 0);
        $payload = $request->parsedBody();
        $notes = isset($payload['notes']) ? (string) $payload['notes'] : null;

        $alert = $this->service->acknowledgeAlert($id, $userId, $notes, $request->ipAddress(), $request->header('User-Agent'));

        return new Response(200, ['data' => $alert->toArray()]);
    }

    public function resolve(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.alerts');

        $id = (int) ($request->attributes()['route_params']['id'] ?? 0);
        $payload = $request->parsedBody();
        $notes = isset($payload['notes']) ? (string) $payload['notes'] : null;

        $alert = $this->service->resolveAlert($id, $userId, $notes, $request->ipAddress(), $request->header('User-Agent'));

        return new Response(200, ['data' => $alert->toArray()]);
    }

    public function dismiss(Request $request): Response
    {
        $userId = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->permissionMiddleware->authorize($userId, 'monitoring.alerts');

        $id = (int) ($request->attributes()['route_params']['id'] ?? 0);
        $payload = $request->parsedBody();
        $notes = isset($payload['notes']) ? (string) $payload['notes'] : null;

        $alert = $this->service->dismissAlert($id, $userId, $notes, $request->ipAddress(), $request->header('User-Agent'));

        return new Response(200, ['data' => $alert->toArray()]);
    }
}
