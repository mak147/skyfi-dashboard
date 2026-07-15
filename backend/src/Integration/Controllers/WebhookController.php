<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\DTOs\CreateWebhookData;
use SkyFi\Integration\DTOs\UpdateWebhookData;
use SkyFi\Integration\DTOs\WebhookListFilters;
use SkyFi\Integration\Services\WebhookService;
use SkyFi\Integration\Validators\WebhookValidator;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class WebhookController
{
    public function __construct(
        private readonly WebhookService $service,
        private readonly WebhookValidator $validator,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'integration.webhooks');
        $result = $this->service->list(WebhookListFilters::fromQuery($r->query()));

        return new Response(200, [
            'data' => array_map(
                static fn($w) => ['type' => 'webhooks', 'id' => (string) $w->id(), 'attributes' => $w->toArray()],
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
        $webhook = $this->service->get($this->id($r));

        return ApiResponse::resource('webhooks', (string) $webhook->id(), $webhook->toArray());
    }

    public function store(Request $r): Response
    {
        $userId = $this->can($r, 'integration.webhooks');
        $data = CreateWebhookData::fromArray($r->body());
        $this->validator->create($data);
        $webhook = $this->service->create($userId, $data);

        return new Response(201, [
            'data' => ['type' => 'webhooks', 'id' => (string) $webhook->id(), 'attributes' => $webhook->toArray()],
        ]);
    }

    public function update(Request $r): Response
    {
        $userId = $this->can($r, 'integration.webhooks');
        $data = UpdateWebhookData::fromArray($r->body());
        $this->validator->update($data);
        $webhook = $this->service->update($this->id($r), $userId, $data);

        return ApiResponse::resource('webhooks', (string) $webhook->id(), $webhook->toArray());
    }

    public function destroy(Request $r): Response
    {
        $this->can($r, 'integration.webhooks');
        $this->service->delete($this->id($r));

        return ApiResponse::noContent();
    }

    public function rotateSecret(Request $r): Response
    {
        $this->can($r, 'integration.webhooks');
        $result = $this->service->rotateSecret($this->id($r));

        return new Response(200, [
            'data' => [
                'type' => 'webhooks',
                'id' => (string) $result['webhook']->id(),
                'attributes' => $result['webhook']->toArray(),
                'meta' => ['new_secret' => $result['new_secret']],
            ],
        ]);
    }

    public function test(Request $r): Response
    {
        $this->can($r, 'integration.webhooks');
        $result = $this->service->test($this->id($r), $r->body()['payload'] ?? []);

        return new Response(200, [
            'data' => ['type' => 'webhook-tests', 'id' => (string) $this->id($r), 'attributes' => $result],
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
