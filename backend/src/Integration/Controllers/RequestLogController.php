<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\DTOs\RequestLogFilters;
use SkyFi\Integration\Services\RequestLogService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class RequestLogController
{
    public function __construct(
        private readonly RequestLogService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'integration.manage');
        $result = $this->service->list(RequestLogFilters::fromQuery($r->query()));

        return new Response(200, [
            'data' => array_map(
                static fn($l) => ['type' => 'request-logs', 'id' => (string) $l->id(), 'attributes' => $l->toArray()],
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
