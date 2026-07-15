<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Controllers;

use SkyFi\Notifications\DTOs\CreateTemplateData;
use SkyFi\Notifications\DTOs\UpdateTemplateData;
use SkyFi\Notifications\Services\TemplateService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class NotificationTemplateController
{
    public function __construct(
        private readonly TemplateService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'notifications.templates');
        $result = $this->service->list($r->query());

        return new Response(200, [
            'data' => array_map(
                static fn ($t) => [
                    'type' => 'notification-templates',
                    'id' => (string) $t->id(),
                    'attributes' => $t->toArray(),
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
        $this->can($r, 'notifications.templates');
        $t = $this->service->get($this->id($r));

        return ApiResponse::resource('notification-templates', (string) $t->id(), $t->toArray());
    }

    public function store(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.templates');
        $t = $this->service->create(CreateTemplateData::fromArray($r->body()), $userId);

        return ApiResponse::resource('notification-templates', (string) $t->id(), $t->toArray(), 201);
    }

    public function update(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.templates');
        $t = $this->service->update($this->id($r), UpdateTemplateData::fromArray($r->body()), $userId);

        return ApiResponse::resource('notification-templates', (string) $t->id(), $t->toArray());
    }

    public function destroy(Request $r): Response
    {
        $this->can($r, 'notifications.templates');
        $this->service->delete($this->id($r));

        return ApiResponse::noContent();
    }

    public function preview(Request $r): Response
    {
        $this->can($r, 'notifications.templates');
        $body = $r->body();
        $sample = is_array($body['sample'] ?? null) ? $body['sample'] : (is_array($body['data'] ?? null) ? $body['data'] : $body);
        $preview = $this->service->preview($this->id($r), is_array($sample) ? $sample : []);

        return new Response(200, [
            'data' => [
                'type' => 'notification-template-previews',
                'id' => (string) $this->id($r),
                'attributes' => $preview,
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
