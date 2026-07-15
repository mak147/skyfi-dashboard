<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Controllers;

use SkyFi\Inventory\Services\CatalogService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class InventoryLookupController
{
    public function __construct(
        private readonly CatalogService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function lookup(Request $request): Response
    {
        $this->can($request);
        $resource = (string) ($request->attributes()['route_params']['resource'] ?? '');
        return new Response(200, ['data' => $this->service->lookup($resource, (string) ($request->query()['search'] ?? ''))]);
    }

    public function search(Request $request): Response
    {
        $this->can($request);
        $search = trim((string) ($request->query()['q'] ?? ''));
        if (mb_strlen($search) < 2) {
            return new Response(200, ['data' => ['products' => [], 'assets' => [], 'warehouses' => [], 'vendors' => []]]);
        }
        return new Response(200, ['data' => [
            'products' => $this->service->lookup('products', $search),
            'assets' => $this->service->lookup('assets', $search),
            'warehouses' => $this->service->lookup('warehouses', $search),
            'vendors' => $this->service->lookup('vendors', $search),
        ]]);
    }

    private function can(Request $request): void
    {
        $this->auth->authorize((int) ($request->attributes()['claims']['sub'] ?? 0), 'inventory.view');
    }
}
