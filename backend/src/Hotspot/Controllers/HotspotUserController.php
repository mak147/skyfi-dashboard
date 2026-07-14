<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Controllers;

use SkyFi\Hotspot\Contracts\HotspotUserServiceContract;
use SkyFi\Hotspot\DTOs\BulkImportUserData;
use SkyFi\Hotspot\DTOs\CreateHotspotUserData;
use SkyFi\Hotspot\DTOs\HotspotUserListFilters;
use SkyFi\Hotspot\DTOs\UpdateHotspotUserData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class HotspotUserController
{
    public function __construct(
        private readonly HotspotUserServiceContract $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize($request, 'hotspot.view');
        $result = $this->service->list(HotspotUserListFilters::fromQuery($request->query()));

        return new Response(200, [
            'data' => array_map(static fn ($user): array => [
                'type' => 'hotspot-users',
                'id' => (string) $user->id(),
                'attributes' => $user->toArray(),
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
        $this->authorize($request, 'hotspot.view');
        $user = $this->service->get($this->routeId($request));

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function store(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.create');
        $user = $this->service->create(
            CreateHotspotUserData::fromArray($request->body()),
            $actorId,
            $request->ipAddress(),
            $request->userAgent()
        );

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.update');
        $user = $this->service->update(
            $this->routeId($request),
            UpdateHotspotUserData::fromArray($request->body()),
            $actorId,
            $request->ipAddress(),
            $request->userAgent()
        );

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.delete');
        $this->service->delete($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    public function enable(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.update');
        $user = $this->service->setEnabled($this->routeId($request), true, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function disable(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.update');
        $user = $this->service->setEnabled($this->routeId($request), false, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function suspend(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.manage');
        $user = $this->service->suspend($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function resume(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.manage');
        $user = $this->service->resume($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function resetPassword(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.update');
        $body = $request->body();
        $password = trim((string) ($body['password'] ?? ''));

        $user = $this->service->resetPassword($this->routeId($request), $password, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function assignProfile(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.update');
        $body = $request->body();
        $profileId = (int) ($body['profile_id'] ?? 0);

        $user = $this->service->assignProfile($this->routeId($request), $profileId, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function assignRouter(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.update');
        $body = $request->body();
        $routerId = (int) ($body['router_id'] ?? 0);

        $user = $this->service->assignRouter($this->routeId($request), $routerId, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-users', (string) $user->id(), $user->toArray());
    }

    public function bulkImport(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.create');
        $data = BulkImportUserData::fromArray($request->body());

        $result = $this->service->bulkImport($data, $actorId, $request->ipAddress(), $request->userAgent());

        return new Response(200, [
            'data' => [
                'type' => 'hotspot-bulk-import-results',
                'id' => 'import-' . time(),
                'attributes' => $result,
            ],
        ]);
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
