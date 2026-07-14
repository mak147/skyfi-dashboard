<?php

declare(strict_types=1);

namespace SkyFi\Pppoe\Controllers;

use SkyFi\Pppoe\Contracts\PppoeServiceContract;
use SkyFi\Pppoe\DTOs\CreatePppoeAccountData;
use SkyFi\Pppoe\DTOs\PppoeListFilters;
use SkyFi\Pppoe\DTOs\UpdatePppoeAccountData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class PppoeAccountController
{
    public function __construct(
        private readonly PppoeServiceContract $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize($request, 'pppoe.view');
        $result = $this->service->list(PppoeListFilters::fromQuery($request->query()));

        return new Response(200, [
            'data' => array_map(static fn ($account): array => [
                'type' => 'pppoe-accounts',
                'id' => (string) $account->id(),
                'attributes' => $account->toArray(),
            ], $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->authorize($request, 'pppoe.view');
        $account = $this->service->get($this->routeId($request));

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    public function store(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.create');
        $account = $this->service->create(
            CreatePppoeAccountData::fromArray($request->body()),
            $actorId,
            $request->ipAddress(),
            $request->userAgent()
        );

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.update');
        $account = $this->service->update(
            $this->routeId($request),
            UpdatePppoeAccountData::fromArray($request->body()),
            $actorId,
            $request->ipAddress(),
            $request->userAgent()
        );

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.delete');
        $this->service->delete($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    public function enable(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.enable');
        $account = $this->service->setEnabled($this->routeId($request), true, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    public function disable(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.disable');
        $account = $this->service->setEnabled($this->routeId($request), false, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    public function suspend(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.manage');
        $account = $this->service->suspend($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    public function resume(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.manage');
        $account = $this->service->resume($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    public function reconnect(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.manage');
        $this->service->reconnect($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return new Response(200, ['data' => ['message' => 'Reconnection triggered successfully.']]);
    }

    public function resetPassword(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.update');
        $body = $request->body();
        $password = trim((string) ($body['password'] ?? ''));

        $account = $this->service->resetPassword($this->routeId($request), $password, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    public function changePackage(Request $request): Response
    {
        $actorId = $this->authorize($request, 'pppoe.update');
        $body = $request->body();
        $packageId = (int) ($body['package_id'] ?? 0);
        $profile = isset($body['profile']) ? trim((string) $body['profile']) : null;

        $account = $this->service->changePackage($this->routeId($request), $packageId, $profile, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('pppoe-accounts', (string) $account->id(), $account->toArray());
    }

    private function authorize(Request $request, string $permission): int
    {
        $claims = $request->attributes()['claims'] ?? [];
        $userId = isset($claims['sub']) ? (int) $claims['sub'] : 0;
        $this->authorizer->authorize($userId, $permission);

        return $userId;
    }

    private function routeId(Request $request): int
    {
        return (int) (($request->attributes()['route_params'] ?? [])['id'] ?? 0);
    }
}
