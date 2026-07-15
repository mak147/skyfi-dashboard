<?php

declare(strict_types=1);

namespace SkyFi\Audit\Controllers;

use SkyFi\Audit\Contracts\AuditServiceContract;
use SkyFi\Audit\DTOs\ActivityFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ActivityController
{
    public function __construct(
        private readonly AuditServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'audit.view');
        $filters = ActivityFilters::fromQuery($r->query());
        $result = $this->service->searchActivity($filters);

        return new Response(200, [
            'data' => array_map(
                static fn(array $item) => [
                    'type' => 'activity-events',
                    'id' => (string) ($item['id'] ?? ''),
                    'attributes' => $item,
                ],
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

    public function userActivity(Request $r): Response
    {
        $this->can($r, 'audit.view');
        $params = $r->attributes()['route_params'] ?? [];
        $targetUserId = (int) ($params['id'] ?? 0);

        $filters = ActivityFilters::fromQuery(array_merge($r->query(), ['user_id' => $targetUserId]));
        $result = $this->service->searchActivity($filters);

        return new Response(200, [
            'data' => array_map(
                static fn(array $item) => [
                    'type' => 'activity-events',
                    'id' => (string) ($item['id'] ?? ''),
                    'attributes' => $item,
                ],
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

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);
        return $userId;
    }
}
