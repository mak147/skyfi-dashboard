<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\Contracts\ConnectorServiceContract;
use SkyFi\Integration\DTOs\UpdateConnectorData;
use SkyFi\Integration\Services\ConnectorRegistry;
use SkyFi\Integration\Validators\ConnectorValidator;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ConnectorController
{
    public function __construct(
        private readonly ConnectorServiceContract $service,
        private readonly ConnectorRegistry $registry,
        private readonly ConnectorValidator $validator,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'integration.view');
        $connectors = $this->service->list();

        return new Response(200, [
            'data' => array_map(
                static fn($c) => ['type' => 'connectors', 'id' => (string) $c->id(), 'attributes' => $c->toArray()],
                $connectors,
            ),
        ]);
    }

    public function show(Request $r): Response
    {
        $this->can($r, 'integration.view');
        $type = (string) ($r->attributes()['route_params']['type'] ?? '');
        $connector = $this->service->get($type);
        $impl = $this->registry->get($type);

        $attrs = $connector->toArray();
        if ($impl !== null) {
            $attrs['_meta'] = [
                'category' => $impl->category(),
                'default_config' => $impl->defaultConfig(),
            ];
        }

        return ApiResponse::resource('connectors', $type, $attrs);
    }

    public function update(Request $r): Response
    {
        $userId = $this->can($r, 'integration.manage');
        $type = (string) ($r->attributes()['route_params']['type'] ?? '');
        $data = UpdateConnectorData::fromArray($r->body());
        $this->validator->update($data);
        $connector = $this->service->update($type, $userId, $data);

        return ApiResponse::resource('connectors', $type, $connector->toArray());
    }

    public function test(Request $r): Response
    {
        $this->can($r, 'integration.manage');
        $type = (string) ($r->attributes()['route_params']['type'] ?? '');
        $result = $this->service->test($type);

        return new Response(200, [
            'data' => ['type' => 'connector-tests', 'id' => $type, 'attributes' => $result],
        ]);
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);

        return $userId;
    }
}
