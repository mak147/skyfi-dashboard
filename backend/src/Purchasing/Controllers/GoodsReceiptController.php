<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Controllers;

use SkyFi\Purchasing\DTOs\GoodsReceiptData;
use SkyFi\Purchasing\DTOs\GoodsReceiptListFilters;
use SkyFi\Purchasing\Services\GoodsReceiptService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class GoodsReceiptController
{
    public function __construct(
        private readonly GoodsReceiptService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $result = $this->service->list(GoodsReceiptListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($r): array => ['type' => 'goods-receipts', 'id' => (string) $r->id(), 'attributes' => $r->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $item = $this->service->get($this->id($request));
        return ApiResponse::resource('goods-receipts', (string) $item->id(), $item->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.receive');
        $body = $request->body();
        $item = $this->service->create(GoodsReceiptData::fromArray($body), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('goods-receipts', (string) $item->id(), $item->toArray(), 201);
    }

    public function returnToSupplier(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.receive');
        $item = $this->service->returnToSupplier($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('goods-receipts', (string) $item->id(), $item->toArray());
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
