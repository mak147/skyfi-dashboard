<?php

declare(strict_types=1);

namespace SkyFi\Inventory\Controllers;

use SkyFi\Inventory\DTOs\TransferData;
use SkyFi\Inventory\DTOs\TransferListFilters;
use SkyFi\Inventory\Services\TransferService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class TransferController
{
    public function __construct(
        private readonly TransferService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $result = $this->service->list(TransferListFilters::fromQuery($request->query()));
        return new Response(200, [
            'data' => array_map(static fn($transfer): array => ['type' => 'inventory-transfers', 'id' => (string) $transfer->id(), 'attributes' => $transfer->toArray()], $result['items']),
            'meta' => ['current_page' => $result['page'], 'per_page' => $result['perPage'], 'total' => $result['total'], 'last_page' => $result['lastPage']],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->can($request, 'inventory.view');
        $transfer = $this->service->get($this->id($request));
        return ApiResponse::resource('inventory-transfers', (string) $transfer->id(), $transfer->toArray());
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.transfer');
        $transfer = $this->service->create(TransferData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-transfers', (string) $transfer->id(), $transfer->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.transfer');
        $transfer = $this->service->update($this->id($request), TransferData::fromArray($request->body()), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('inventory-transfers', (string) $transfer->id(), $transfer->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actor = $this->can($request, 'inventory.transfer');
        $this->service->delete($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::noContent();
    }

    public function action(Request $request): Response
    {
        $action = (string) ($request->attributes()['inventory_transfer_action'] ?? '');
        $permission = $action === 'approve' ? 'inventory.manage' : 'inventory.transfer';
        $actor = $this->can($request, $permission);
        $transfer = match ($action) {
            'submit' => $this->service->submit($this->id($request), $actor, $request->ipAddress(), $request->userAgent()),
            'approve' => $this->service->approve($this->id($request), $actor, $request->ipAddress(), $request->userAgent()),
            'dispatch' => $this->service->dispatch($this->id($request), $request->body(), $actor, $request->ipAddress(), $request->userAgent()),
            'receive' => $this->service->receive($this->id($request), $request->body(), $actor, $request->ipAddress(), $request->userAgent()),
            'cancel' => $this->service->cancel($this->id($request), (string) ($request->body()['reason'] ?? ''), $actor, $request->ipAddress(), $request->userAgent()),
            default => throw new \InvalidArgumentException('Unsupported transfer action.'),
        };
        return ApiResponse::resource('inventory-transfers', (string) $transfer->id(), $transfer->toArray());
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
