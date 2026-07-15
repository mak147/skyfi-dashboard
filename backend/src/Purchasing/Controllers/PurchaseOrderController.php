<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Controllers;

use SkyFi\Purchasing\DTOs\PurchaseOrderData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;
use SkyFi\Purchasing\Services\PurchaseOrderService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class PurchaseOrderController
{
    public function __construct(
        private readonly PurchaseOrderService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $result = $this->service->list(PurchaseOrderListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($o): array => ['type' => 'purchase-orders', 'id' => (string) $o->id(), 'attributes' => $o->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $item = $this->service->get($this->id($request));
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.create');
        $item = $this->service->create(PurchaseOrderData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.update');
        $item = $this->service->update($this->id($request), PurchaseOrderData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray());
    }

    public function submit(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.create');
        $item = $this->service->submit($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray());
    }

    public function approve(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.approve');
        $body = $request->body();
        $item = $this->service->approve($this->id($request), $actor, $body['comments'] ?? null, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray());
    }

    public function reject(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.approve');
        $body = $request->body();
        $item = $this->service->reject($this->id($request), $actor, $body['comments'] ?? null, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray());
    }

    public function cancel(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.update');
        $item = $this->service->cancel($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray());
    }

    public function close(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.manage');
        $item = $this->service->close($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-orders', (string) $item->id(), $item->toArray());
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
