<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Controllers;

use SkyFi\Inventory\DTOs\WarehouseData;
use SkyFi\Inventory\Services\WarehouseService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class WarehouseController
{
    public function __construct(
        private readonly WarehouseService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $query = $request->query();
        $filters = is_array($query['filter'] ?? null) ? [...$query['filter'], 'sort' => $query['sort'] ?? '-created_at'] : $query;
        $page = is_array($query['page'] ?? null) ? $query['page'] : [];
        $filters['page'] = (int) ($page['number'] ?? $query['page'] ?? 1);
        $filters['per_page'] = (int) ($page['size'] ?? $query['per_page'] ?? 20);
        $result = $this->service->list($filters);
        return new Response(200, [
            'data' => array_map(static fn($warehouse): array => ['type' => 'warehouses', 'id' => (string) $warehouse->id(), 'attributes' => $warehouse->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $warehouse = $this->service->get($this->id($request));
        return ApiResponse::resource('warehouses', (string) $warehouse->id(), $warehouse->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.create');
        $warehouse = $this->service->create(WarehouseData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('warehouses', (string) $warehouse->id(), $warehouse->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.update');
        $warehouse = $this->service->update($this->id($request), WarehouseData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('warehouses', (string) $warehouse->id(), $warehouse->toArray());
    }

    public function changeStatus(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.manage');
        $old = $this->service->get($this->id($request))->toArray();
        $old['status'] = (string) ($request->body()['status'] ?? '');
        $warehouse = $this->service->update($this->id($request), WarehouseData::fromArray($old), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('warehouses', (string) $warehouse->id(), $warehouse->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.delete');
        $this->service->delete($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::noContent();
    }

    public function locations(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        return new Response(200, ['data' => $this->service->locations($this->id($request))]);
    }

    public function storeLocation(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.create');
        return new Response(201, ['data' => $this->service->saveLocation($this->id($request), null, $request->body(), $actor, $request->ipAddress(), $request->userAgent())]);
    }

    public function updateLocation(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.update');
        return new Response(200, ['data' => $this->service->saveLocation($this->id($request), $this->locationId($request), $request->body(), $actor, $request->ipAddress(), $request->userAgent())]);
    }

    public function destroyLocation(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.delete');
        $this->service->deleteLocation($this->id($request), $this->locationId($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::noContent();
    }

    private function can(Request $request, string $permission): int
    {
        $actor = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($actor, $permission);
        return $actor;
    }

    private function id(Request $request): int
    {
        return (int) ($request->attributes()['route_params']['id'] ?? 0);
    }

    private function locationId(Request $request): int
    {
        return (int) ($request->attributes()['route_params']['locationId'] ?? 0);
    }
}
