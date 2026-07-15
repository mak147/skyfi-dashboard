<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\Contracts\ApiKeyServiceContract;
use SkyFi\Integration\DTOs\ApiKeyListFilters;
use SkyFi\Integration\DTOs\CreateApiKeyData;
use SkyFi\Integration\DTOs\UpdateApiKeyData;
use SkyFi\Integration\Validators\ApiKeyValidator;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ApiKeyController
{
    public function __construct(
        private readonly ApiKeyServiceContract $service,
        private readonly ApiKeyValidator $validator,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $userId = $this->can($r, 'integration.apikeys');
        $result = $this->service->list($userId, ApiKeyListFilters::fromQuery($r->query()));

        return new Response(200, [
            'data' => array_map(
                static fn($k) => ['type' => 'api-keys', 'id' => (string) $k->id(), 'attributes' => $k->toArray()],
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
        $userId = $this->can($r, 'integration.apikeys');
        $key = $this->service->get($this->id($r), $userId);

        return ApiResponse::resource('api-keys', (string) $key->id(), $key->toArray());
    }

    public function store(Request $r): Response
    {
        $userId = $this->can($r, 'integration.apikeys');
        $data = CreateApiKeyData::fromArray($r->body());
        $this->validator->create($data);
        $result = $this->service->create($userId, $data);

        return new Response(201, [
            'data' => [
                'type' => 'api-keys',
                'id' => (string) $result['key']->id(),
                'attributes' => $result['key']->toArray(),
                'meta' => ['plain_text_key' => $result['plain_text_key']],
            ],
        ]);
    }

    public function update(Request $r): Response
    {
        $userId = $this->can($r, 'integration.apikeys');
        $data = UpdateApiKeyData::fromArray($r->body());
        $this->validator->update($data);
        $key = $this->service->update($this->id($r), $userId, $data);

        return ApiResponse::resource('api-keys', (string) $key->id(), $key->toArray());
    }

    public function destroy(Request $r): Response
    {
        $userId = $this->can($r, 'integration.apikeys');
        $this->service->delete($this->id($r), $userId);

        return ApiResponse::noContent();
    }

    public function regenerate(Request $r): Response
    {
        $userId = $this->can($r, 'integration.apikeys');
        $result = $this->service->regenerate($this->id($r), $userId);

        return new Response(200, [
            'data' => [
                'type' => 'api-keys',
                'id' => (string) $result['key']->id(),
                'attributes' => $result['key']->toArray(),
                'meta' => ['plain_text_key' => $result['plain_text_key']],
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
