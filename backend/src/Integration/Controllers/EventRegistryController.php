<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\Services\EventRegistryService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class EventRegistryController
{
    public function __construct(
        private readonly EventRegistryService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'integration.view');
        $page = (int) ($r->query()['page'] ?? 1);
        $perPage = (int) ($r->query()['per_page'] ?? 50);
        $sourceModule = $r->query()['source_module'] ?? null;
        if (is_string($sourceModule) && $sourceModule === '') {
            $sourceModule = null;
        }
        $result = $this->service->list(max(1, $page), max(1, min(100, $perPage)), $sourceModule);

        return new Response(200, [
            'data' => array_map(
                static fn($e) => ['type' => 'event-registry', 'id' => (string) $e->id(), 'attributes' => $e->toArray()],
                $result['items'],
            ),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function show(Request $r): Response
    {
        $this->can($r, 'integration.view');
        $event = $this->service->get($this->id($r));

        return ApiResponse::resource('event-registry', (string) $event->id(), $event->toArray());
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);

        return $userId;
    }

    private function id(Request $r): int
    {
        return (int) ($r->attributes()['route_params']['id'] ?? 0);
    }
}
