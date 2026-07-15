<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Controllers;

use SkyFi\Notifications\Contracts\NotificationEventRepositoryContract;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class NotificationEventController
{
    public function __construct(
        private readonly NotificationEventRepositoryContract $events,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'notifications.manage');
        $result = $this->events->list($r->query());

        return new Response(200, [
            'data' => array_map(
                static fn ($e) => [
                    'type' => 'notification-events',
                    'id' => (string) $e->id(),
                    'attributes' => $e->toArray(),
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

    public function show(Request $r): Response
    {
        $this->can($r, 'notifications.manage');
        $id = (int) ($r->attributes()['route_params']['id'] ?? 0);
        $e = $this->events->find($id) ?? throw new NotFoundException('Notification event not found.');

        return ApiResponse::resource('notification-events', (string) $e->id(), $e->toArray());
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);

        return $userId;
    }
}
