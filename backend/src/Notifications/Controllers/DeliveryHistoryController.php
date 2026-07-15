<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Controllers;

use SkyFi\Notifications\Contracts\DeliveryHistoryRepositoryContract;
use SkyFi\Notifications\DTOs\DeliveryListFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class DeliveryHistoryController
{
    public function __construct(
        private readonly DeliveryHistoryRepositoryContract $deliveries,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'notifications.manage');
        $result = $this->deliveries->list(DeliveryListFilters::fromQuery($r->query()));

        return new Response(200, [
            'data' => array_map(
                static fn ($d) => [
                    'type' => 'notification-deliveries',
                    'id' => (string) $d->id(),
                    'attributes' => $d->toArray(),
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
        $d = $this->deliveries->find($id) ?? throw new NotFoundException('Delivery record not found.');

        return ApiResponse::resource('notification-deliveries', (string) $d->id(), $d->toArray());
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);

        return $userId;
    }
}
