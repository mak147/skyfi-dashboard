<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Controllers;

use SkyFi\Mikrotik\Contracts\RouterDiscoveryServiceContract;
use SkyFi\Mikrotik\Contracts\RouterHealthServiceContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\CreateRouterData;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\DTOs\UpdateRouterData;
use SkyFi\Mikrotik\Services\RouterTaxonomyService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class RouterController
{
    public function __construct(
        private readonly RouterServiceContract $routers,
        private readonly RouterDiscoveryServiceContract $discovery,
        private readonly RouterHealthServiceContract $health,
        private readonly RouterTaxonomyService $taxonomy,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.view');
        $result = $this->routers->list(RouterListFilters::fromQuery($request->query()));

        return new Response(200, [
            'data' => array_map(static fn ($router): array => [
                'type' => 'mikrotik-routers',
                'id' => (string) $router->id(),
                'attributes' => $router->toArray(),
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
        $this->authorize($request, 'mikrotik.view');
        $router = $this->routers->get($this->routeId($request));

        return ApiResponse::resource('mikrotik-routers', (string) $router->id(), $router->toArray());
    }

    public function store(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.create');
        $router = $this->routers->create(CreateRouterData::fromArray($request->body()), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-routers', (string) $router->id(), $router->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.update');
        $router = $this->routers->update($this->routeId($request), UpdateRouterData::fromArray($request->body()), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-routers', (string) $router->id(), $router->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.delete');
        $this->routers->delete($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    public function enable(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.update');
        $router = $this->routers->setEnabled($this->routeId($request), true, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-routers', (string) $router->id(), $router->toArray());
    }

    public function disable(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.update');
        $router = $this->routers->setEnabled($this->routeId($request), false, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-routers', (string) $router->id(), $router->toArray());
    }

    public function syncTags(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.update');
        $body = $request->body();
        $tagIds = isset($body['tag_ids']) && is_array($body['tag_ids']) ? $body['tag_ids'] : [];
        $tagIds = array_values(array_unique(array_filter(array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $tagIds))));
        $router = $this->routers->syncTags($this->routeId($request), $tagIds, $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-routers', (string) $router->id(), $router->toArray());
    }

    public function testTransient(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.connect');

        return $this->operationResponse('mikrotik-connection-tests', 'transient', $this->discovery->testTransient($request->body()));
    }

    public function testSaved(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.connect');
        $id = $this->routeId($request);

        return $this->operationResponse('mikrotik-connection-tests', (string) $id, $this->discovery->testSavedRouter($id));
    }

    public function discover(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.connect');
        $id = $this->routeId($request);

        return $this->operationResponse('mikrotik-router-discoveries', (string) $id, $this->discovery->discover($id)->toArray());
    }

    public function latestHealth(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.view');
        $id = $this->routeId($request);
        $health = $this->health->latest($id);
        if ($health === null) {
            return new Response(200, ['data' => null]);
        }

        return ApiResponse::resource('mikrotik-router-health', (string) $health->id, $health->toArray());
    }

    public function checkHealth(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.connect');
        $health = $this->health->check($this->routeId($request));

        return ApiResponse::resource('mikrotik-router-health', (string) $health->id, $health->toArray());
    }

    public function statistics(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.view');
        $id = $this->routeId($request);
        $router = $this->routers->get($id);
        $health = $this->health->latest($id);

        return $this->operationResponse('mikrotik-router-statistics', (string) $id, [
            'router_id' => $id,
            'status' => $router->toArray()['last_connection_status'],
            'health' => $health?->toArray(),
            'traffic_summary_available' => $health?->trafficRxBytes !== null || $health?->trafficTxBytes !== null,
        ]);
    }

    public function groups(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.view');

        return new Response(200, ['data' => array_map(static fn ($group): array => [
            'type' => 'mikrotik-router-groups',
            'id' => (string) $group->id(),
            'attributes' => $group->toArray(),
        ], $this->taxonomy->groups())]);
    }

    public function createGroup(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.create');
        $group = $this->taxonomy->createGroup($request->body(), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-router-groups', (string) $group->id(), $group->toArray(), 201);
    }

    public function updateGroup(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.update');
        $group = $this->taxonomy->updateGroup($this->routeId($request), $request->body(), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-router-groups', (string) $group->id(), $group->toArray());
    }

    public function deleteGroup(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.delete');
        $this->taxonomy->deleteGroup($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    public function tags(Request $request): Response
    {
        $this->authorize($request, 'mikrotik.view');

        return new Response(200, ['data' => array_map(static fn ($tag): array => [
            'type' => 'mikrotik-router-tags',
            'id' => (string) $tag->id(),
            'attributes' => $tag->toArray(),
        ], $this->taxonomy->tags())]);
    }

    public function createTag(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.create');
        $tag = $this->taxonomy->createTag($request->body(), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-router-tags', (string) $tag->id(), $tag->toArray(), 201);
    }

    public function updateTag(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.update');
        $tag = $this->taxonomy->updateTag($this->routeId($request), $request->body(), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('mikrotik-router-tags', (string) $tag->id(), $tag->toArray());
    }

    public function deleteTag(Request $request): Response
    {
        $actorId = $this->authorize($request, 'mikrotik.delete');
        $this->taxonomy->deleteTag($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    /** @param array<string, mixed> $attributes */
    private function operationResponse(string $type, string $id, array $attributes): Response
    {
        return ApiResponse::resource($type, $id, $attributes);
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
