<?php

declare(strict_types=1);

namespace SkyFi\Connections\Controllers;

use SkyFi\Connections\Contracts\ConnectionServiceContract;
use SkyFi\Connections\Data\ConnectionListFilters;
use SkyFi\Connections\Data\CreateConnectionData;
use SkyFi\Connections\Data\UpdateConnectionData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ConnectionController
{
    public function __construct(
        private readonly ConnectionServiceContract $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    private function getUserIdFromRequest(Request $request): int
    {
        $claims = $request->attributes()['claims'] ?? null;
        return $claims && isset($claims['sub']) ? (int) $claims['sub'] : 0;
    }

    public function index(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.view');

        $filters = ConnectionListFilters::fromQuery($request->query());
        $result = $this->service->list($filters);

        $data = array_map(
            static fn($connection): array => [
                'type' => 'connections',
                'id' => (string) $connection->id(),
                'attributes' => $connection->toArray(),
            ],
            $result['items']
        );

        return new Response(200, [
            'data' => $data,
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.view');

        $params = $request->attributes()['route_params'] ?? [];
        $connection = $this->service->get((int) ($params['id'] ?? 0));

        return ApiResponse::resource('connections', (string) $connection->id(), $connection->toArray());
    }

    public function store(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.create');

        $data = CreateConnectionData::fromArray($request->body());
        $connection = $this->service->create($data, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('connections', (string) $connection->id(), $connection->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.update');

        $params = $request->attributes()['route_params'] ?? [];
        $data = UpdateConnectionData::fromArray($request->body());
        $connection = $this->service->update((int) ($params['id'] ?? 0), $data, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('connections', (string) $connection->id(), $connection->toArray());
    }

    public function destroy(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.delete');

        $params = $request->attributes()['route_params'] ?? [];
        $this->service->delete((int) ($params['id'] ?? 0), $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    public function activate(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.activate');

        $params = $request->attributes()['route_params'] ?? [];
        $connection = $this->service->changeStatus((int) ($params['id'] ?? 0), 'active', $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('connections', (string) $connection->id(), $connection->toArray());
    }

    public function suspend(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.suspend');

        $params = $request->attributes()['route_params'] ?? [];
        $connection = $this->service->changeStatus((int) ($params['id'] ?? 0), 'suspended', $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('connections', (string) $connection->id(), $connection->toArray());
    }

    public function disconnect(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.disconnect');

        $params = $request->attributes()['route_params'] ?? [];
        $connection = $this->service->changeStatus((int) ($params['id'] ?? 0), 'disconnected', $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('connections', (string) $connection->id(), $connection->toArray());
    }

    public function transfer(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'connections.transfer');

        $params = $request->attributes()['route_params'] ?? [];
        $body = $request->body();
        $newCustomerId = (int) ($body['customer_id'] ?? 0);

        $connection = $this->service->update((int) ($params['id'] ?? 0), UpdateConnectionData::fromArray([
            'customer_id' => $newCustomerId,
        ] + $this->service->get((int) ($params['id'] ?? 0))->toArray()), $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('connections', (string) $connection->id(), $connection->toArray());
    }
}
