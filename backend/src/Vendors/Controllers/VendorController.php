<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Controllers;

use SkyFi\Vendors\DTOs\VendorData;
use SkyFi\Vendors\DTOs\VendorListFilters;
use SkyFi\Vendors\Services\VendorService;
use SkyFi\Vendors\Services\VendorPerformanceService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class VendorController
{
    public function __construct(
        private readonly VendorService $service,
        private readonly VendorPerformanceService $performanceService,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'vendors.view');
        $result = $this->service->list(VendorListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($v): array => ['type' => 'vendors', 'id' => (string) $v->id(), 'attributes' => $v->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'vendors.view');
        $id = $this->id($request);
        $item = $this->service->get($id);
        $metrics = $this->performanceService->getMetrics($id);

        $attributes = array_merge($item->toArray(), ['performance_metrics' => $metrics]);
        return ApiResponse::resource('vendors', (string) $item->id(), $attributes);
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'vendors.create');
        $item = $this->service->create(VendorData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('vendors', (string) $item->id(), $item->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'vendors.update');
        $item = $this->service->update($this->id($request), VendorData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('vendors', (string) $item->id(), $item->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actor = $this->can($request, 'vendors.delete');
        $item = $this->service->archive($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('vendors', (string) $item->id(), $item->toArray());
    }

    public function activate(Request $request): Response
    {
        $actor = $this->can($request, 'vendors.manage');
        $item = $this->service->activate($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('vendors', (string) $item->id(), $item->toArray());
    }

    public function purchasingHistory(Request $request): Response
    {
        $this->can($request, 'vendors.view');
        $id = $this->id($request);
        $history = $this->service->getPurchasingHistory($id);
        return new Response(200, ['data' => $history]);
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
