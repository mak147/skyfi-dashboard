<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract;
use SkyFi\Integration\Contracts\WebhookDispatcherContract;
use SkyFi\Integration\DTOs\DeliveryListFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class WebhookDeliveryController
{
    public function __construct(
        private readonly WebhookDeliveryRepositoryContract $deliveries,
        private readonly WebhookDispatcherContract $dispatcher,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'integration.webhooks');
        $filters = DeliveryListFilters::fromQuery($r->query());
        // If accessed under a webhook path, filter by webhook_id
        $webhookId = (int) ($r->attributes()['route_params']['webhookId'] ?? 0);
        if ($webhookId > 0) {
            $filters = new DeliveryListFilters(
                webhookId: $webhookId,
                eventKey: $filters->eventKey,
                status: $filters->status,
                page: $filters->page,
                perPage: $filters->perPage,
            );
        }
        $result = $this->deliveries->list($filters);

        return new Response(200, [
            'data' => array_map(
                static fn($d) => ['type' => 'webhook-deliveries', 'id' => (string) $d->id(), 'attributes' => $d->toArray()],
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
        $this->can($r, 'integration.webhooks');
        $delivery = $this->deliveries->find($this->id($r));

        if ($delivery === null) {
            return new Response(404, ['errors' => [['status' => '404', 'title' => 'Not Found', 'detail' => 'Delivery not found.']]]);
        }

        return ApiResponse::resource('webhook-deliveries', (string) $delivery->id(), $delivery->toArray());
    }

    public function retry(Request $r): Response
    {
        $this->can($r, 'integration.webhooks');
        $success = $this->dispatcher->retryDelivery($this->id($r));

        return new Response(200, [
            'data' => [
                'type' => 'webhook-delivery-retries',
                'id' => (string) $this->id($r),
                'attributes' => ['success' => $success],
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
