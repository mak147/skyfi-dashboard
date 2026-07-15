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
        $this->can($request, 'inventory.view');
        return new Response(200, ['data' => $this->service->list($this->resource($request), filter_var($request->query()['active_only'] ?? false, FILTER_VALIDATE_BOOLEAN))]);
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.create');
        $item = $this->service->create($this->resource($request), $request->body(), $actor, $request->ipAddress(), $request->userAgent());
        return new Response(201, ['data' => $item]);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.update');
        $item = $this->service->update($this->resource($request), $this->id($request), $request->body(), $actor, $request->ipAddress(), $request->userAgent());
        return new Response(200, ['data' => $item]);
    }

    public function destroy(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.delete');
        $this->service->delete($this->resource($request), $this->id($request), $actor, $request->ipAddress(), $request->userAgent());
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
