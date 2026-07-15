<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Controllers;

use SkyFi\Notifications\Contracts\NotificationServiceContract;
use SkyFi\Notifications\DTOs\DispatchNotificationData;
use SkyFi\Notifications\DTOs\NotificationListFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class NotificationController
{
    public function __construct(
        private readonly NotificationServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.view');
        $result = $this->service->list($userId, NotificationListFilters::fromQuery($r->query()));

        return new Response(200, [
            'data' => array_map(
                static fn ($n) => [
                    'type' => 'notifications',
                    'id' => (string) $n->id(),
                    'attributes' => $n->toArray(),
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

    public function unreadCount(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.view');

        return new Response(200, [
            'data' => [
                'type' => 'notification-counts',
                'id' => (string) $userId,
                'attributes' => ['unread_count' => $this->service->unreadCount($userId)],
            ],
        ]);
    }

    public function show(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.view');
        $n = $this->service->get($this->id($r), $userId);

        return ApiResponse::resource('notifications', (string) $n->id(), $n->toArray());
    }

    public function markRead(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.view');
        $n = $this->service->markRead($this->id($r), $userId);

        return ApiResponse::resource('notifications', (string) $n->id(), $n->toArray());
    }

    public function markAllRead(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.view');
        $count = $this->service->markAllRead($userId);

        return new Response(200, [
            'data' => [
                'type' => 'notification-bulk',
                'id' => (string) $userId,
                'attributes' => ['marked_read' => $count],
            ],
        ]);
    }

    public function archive(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.view');
        $n = $this->service->archive($this->id($r), $userId);

        return ApiResponse::resource('notifications', (string) $n->id(), $n->toArray());
    }

    public function destroy(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.view');
        $this->service->delete($this->id($r), $userId);

        return ApiResponse::noContent();
    }

    public function catalog(Request $r): Response
    {
        $this->can($r, 'notifications.view');

        return new Response(200, [
            'data' => [
                'type' => 'notification-catalog',
                'id' => 'catalog',
                'attributes' => $this->service->catalog(),
            ],
        ]);
    }

    public function dispatch(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.manage');
        $body = $r->body();
        if (!isset($body['actor_id']) && !isset($body['data']['attributes']['actor_id'])) {
            $body['actor_id'] = $userId;
        }
        $result = $this->service->dispatch(DispatchNotificationData::fromArray($body));

        return new Response(201, [
            'data' => [
                'type' => 'notification-dispatches',
                'id' => (string) ($result['event']['id'] ?? '0'),
                'attributes' => $result,
            ],
        ]);
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
