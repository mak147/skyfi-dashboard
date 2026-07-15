<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Controllers;

use SkyFi\Purchasing\DTOs\SupplierInvoiceData;
use SkyFi\Purchasing\DTOs\PurchaseOrderListFilters;
use SkyFi\Purchasing\Services\SupplierInvoiceService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class SupplierInvoiceController
{
    public function __construct(
        private readonly SupplierInvoiceService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $result = $this->service->list(PurchaseOrderListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($i): array => ['type' => 'supplier-invoices', 'id' => (string) $i->id(), 'attributes' => $i->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        $item = $this->service->get($this->id($request));
        return ApiResponse::resource('supplier-invoices', (string) $item->id(), $item->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.create');
        $item = $this->service->create(SupplierInvoiceData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('supplier-invoices', (string) $item->id(), $item->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'purchasing.update');
        $item = $this->service->update($this->id($request), SupplierInvoiceData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('supplier-invoices', (string) $item->id(), $item->toArray());
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
