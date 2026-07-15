<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Controllers;

use SkyFi\Inventory\DTOs\ProductData;
use SkyFi\Inventory\DTOs\ProductListFilters;
use SkyFi\Inventory\Services\ProductService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ProductController
{
    public function __construct(
        private readonly ProductService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $result = $this->service->list(ProductListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($product): array => ['type' => 'inventory-products', 'id' => (string) $product->id(), 'attributes' => $product->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $product = $this->service->get($this->id($request));
        return ApiResponse::resource('inventory-products', (string) $product->id(), $product->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.create');
        $product = $this->service->create(ProductData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-products', (string) $product->id(), $product->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.update');
        $product = $this->service->update($this->id($request), ProductData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-products', (string) $product->id(), $product->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.delete');
        $this->service->delete($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::noContent();
    }

    public function stock(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        return new Response(200, ['data' => $this->service->stock((int) ($request->query()['warehouse_id'] ?? 0))]);
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
