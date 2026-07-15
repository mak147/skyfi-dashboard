<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Controllers;

use SkyFi\Inventory\Services\CatalogService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class CatalogController
{
    public function __construct(
        private readonly CatalogService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $resource = $this->resource($request);
        $this->can($request, $resource === 'vendors' ? 'vendors.view' : 'inventory.view');
        return new Response(200, ['data' => $this->service->list($resource, filter_var($request->query()['active_only'] ?? false, FILTER_VALIDATE_BOOLEAN))]);
    }

    public function store(Request $request): Response
    {
        $resource = $this->resource($request);
        $actor = $this->can($request, $resource === 'vendors' ? 'vendors.create' : 'inventory.create');
        $item = $this->service->create($resource, $request->body(), $actor, $request->ipAddress(), $request->userAgent());
        return new Response(201, ['data' => $item]);
    }

    public function update(Request $request): Response
    {
        $resource = $this->resource($request);
        $actor = $this->can($request, $resource === 'vendors' ? 'vendors.update' : 'inventory.update');
        $item = $this->service->update($resource, $this->id($request), $request->body(), $actor, $request->ipAddress(), $request->userAgent());
        return new Response(200, ['data' => $item]);
    }

    public function destroy(Request $request): Response
    {
        $resource = $this->resource($request);
        $actor = $this->can($request, $resource === 'vendors' ? 'vendors.delete' : 'inventory.delete');
        $this->service->delete($resource, $this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::noContent();
    }

    private function can(Request $request, string $permission): int
    {
        $actor = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($actor, $permission);
        return $actor;
    }

    private function resource(Request $request): string
    {
        return (string) ($request->attributes()['route_params']['resource'] ?? '');
    }

    private function id(Request $request): int
    {
        return (int) ($request->attributes()['route_params']['id'] ?? 0);
    }
}
