<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Controllers;

use SkyFi\Inventory\DTOs\AssetAssignmentData;
use SkyFi\Inventory\DTOs\AssetData;
use SkyFi\Inventory\DTOs\AssetListFilters;
use SkyFi\Inventory\Services\AssetService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class AssetController
{
    public function __construct(
        private readonly AssetService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $result = $this->service->list(AssetListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($asset): array => ['type' => 'inventory-assets', 'id' => (string) $asset->id(), 'attributes' => $asset->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $asset = $this->service->get($this->id($request));
        return ApiResponse::resource('inventory-assets', (string) $asset->id(), $asset->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.create');
        $asset = $this->service->create(AssetData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-assets', (string) $asset->id(), $asset->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.update');
        $asset = $this->service->update($this->id($request), AssetData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-assets', (string) $asset->id(), $asset->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.delete');
        $this->service->delete($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::noContent();
    }

    public function assign(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.transfer');
        $asset = $this->service->assign($this->id($request), AssetAssignmentData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-assets', (string) $asset->id(), $asset->toArray());
    }

    public function returnToWarehouse(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.transfer');
        $asset = $this->service->returnToWarehouse($this->id($request), (int) ($request->body()['warehouse_location_id'] ?? 0), isset($request->body()['notes']) ? (string) $request->body()['notes'] : null, $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-assets', (string) $asset->id(), $asset->toArray());
    }

    public function changeStatus(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.manage');
        $asset = $this->service->changeStatus($this->id($request), (string) ($request->body()['status'] ?? ''), isset($request->body()['reason']) ? (string) $request->body()['reason'] : null, $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-assets', (string) $asset->id(), $asset->toArray());
    }

    public function timeline(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        return new Response(200, ['data' => $this->service->timeline($this->id($request))]);
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
}
