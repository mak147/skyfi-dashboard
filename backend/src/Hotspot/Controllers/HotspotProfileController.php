<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Controllers;

use SkyFi\Hotspot\Contracts\HotspotProfileServiceContract;
use SkyFi\Hotspot\DTOs\CreateHotspotProfileData;
use SkyFi\Hotspot\DTOs\HotspotProfileListFilters;
use SkyFi\Hotspot\DTOs\UpdateHotspotProfileData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class HotspotProfileController
{
    public function __construct(
        private readonly HotspotProfileServiceContract $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize($request, 'hotspot.view');
        $result = $this->service->list(HotspotProfileListFilters::fromQuery($request->query()));

        return new Response(200, [
            'data' => array_map(static fn ($p): array => [
                'type' => 'hotspot-profiles',
                'id' => (string) $p->id(),
                'attributes' => $p->toArray(),
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
        $profile = $this->service->get($this->routeId($request));

        return ApiResponse::resource('hotspot-profiles', (string) $profile->id(), $profile->toArray());
    }

    public function store(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.create');
        $profile = $this->service->create(
            CreateHotspotProfileData::fromArray($request->body()),
            $actorId,
            $request->ipAddress(),
            $request->userAgent()
        );

        return ApiResponse::resource('hotspot-profiles', (string) $profile->id(), $profile->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.update');
        $profile = $this->service->update(
            $this->routeId($request),
            UpdateHotspotProfileData::fromArray($request->body()),
            $actorId,
            $request->ipAddress(),
            $request->userAgent()
        );

        return ApiResponse::resource('hotspot-profiles', (string) $profile->id(), $profile->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.delete');
        $this->service->delete($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
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
