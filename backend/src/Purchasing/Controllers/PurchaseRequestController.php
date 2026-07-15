<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Controllers;

use SkyFi\Purchasing\DTOs\PurchaseRequestData;
use SkyFi\Purchasing\DTOs\PurchaseRequestListFilters;
use SkyFi\Purchasing\Services\PurchaseRequestService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class PurchaseRequestController
{
    public function __construct(
        private readonly PurchaseRequestService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $result = $this->service->list(PurchaseRequestListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($r): array => ['type' => 'purchase-requests', 'id' => (string) $r->id(), 'attributes' => $r->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $item = $this->service->get($this->id($request));
        return ApiResponse::resource('purchase-requests', (string) $item->id(), $item->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.create');
        $item = $this->service->create(PurchaseRequestData::fromArray($request->body(), $actor), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-requests', (string) $item->id(), $item->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.update');
        $item = $this->service->update($this->id($request), PurchaseRequestData::fromArray($request->body(), $actor), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-requests', (string) $item->id(), $item->toArray());
    }

    public function submit(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.create');
        $item = $this->service->submit($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-requests', (string) $item->id(), $item->toArray());
    }

    public function approve(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.approve');
        $body = $request->body();
        $item = $this->service->approve($this->id($request), $actor, $body['comments'] ?? null, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-requests', (string) $item->id(), $item->toArray());
    }

    public function reject(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.approve');
        $body = $request->body();
        $item = $this->service->reject($this->id($request), $actor, $body['comments'] ?? null, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-requests', (string) $item->id(), $item->toArray());
    }

    public function cancel(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.update');
        $item = $this->service->cancel($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('purchase-requests', (string) $item->id(), $item->toArray());
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
