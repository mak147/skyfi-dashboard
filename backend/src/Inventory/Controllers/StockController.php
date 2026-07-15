<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Controllers;

use SkyFi\Inventory\DTOs\StockMovementListFilters;
use SkyFi\Inventory\DTOs\StockOperationData;
use SkyFi\Inventory\Services\StockService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class StockController
{
    public function __construct(
        private readonly StockService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function dashboard(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        return new Response(200, ['data' => $this->service->dashboard()]);
    }

    public function balances(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $filters = is_array($request->query()['filter'] ?? null) ? $request->query()['filter'] : $request->query();
        return new Response(200, ['data' => $this->service->balances($filters)]);
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $result = $this->service->list(StockMovementListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($movement): array => ['type' => 'inventory-stock-movements', 'id' => (string) $movement->id(), 'attributes' => $movement->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $movement = $this->service->get($this->id($request));
        return ApiResponse::resource('inventory-stock-movements', (string) $movement->id(), $movement->toArray());
    }

    public function post(Request $request): Response
    {
        $operation = (string) ($request->attributes()['inventory_operation'] ?? '');
        $permission = in_array($operation, ['opening_balance', 'adjustment_in', 'adjustment_out', 'return', 'damaged', 'scrap'], true) ? 'inventory.audit' : 'inventory.manage';
        $actor = $this->can($request, $permission);
        $movement = $this->service->post(StockOperationData::fromArray($operation, $request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-stock-movements', (string) $movement->id(), $movement->toArray(), 201);
    }

    public function reverse(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.audit');
        $movement = $this->service->reverse($this->id($request), (string) ($request->body()['reason'] ?? ''), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-stock-movements', (string) $movement->id(), $movement->toArray(), 201);
    }

    public function accountingSettings(Request $request): Response
    {
        $this->can($request, 'inventory.manage');
        return new Response(200, ['data' => $this->service->accountingSettings()]);
    }

    public function updateAccountingSettings(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.manage');
        return new Response(200, ['data' => $this->service->updateAccountingSettings($request->body(), $actor, $request->ipAddress(), $request->userAgent())]);
    }

    public function financePostings(Request $request): Response
    {
        $this->can($request, 'inventory.manage');
        return new Response(200, ['data' => $this->service->financePostings()]);
    }

    public function retryFinancePosting(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.manage');
        return new Response(200, ['data' => $this->service->retryFinancePosting($this->id($request), $actor)]);
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
