<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\DTOs\CreateClientApplicationData;
use SkyFi\Integration\DTOs\UpdateClientApplicationData;
use SkyFi\Integration\Services\ClientApplicationService;
use SkyFi\Integration\Validators\ClientApplicationValidator;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class ClientApplicationController
{
    public function __construct(
        private readonly ClientApplicationService $service,
        private readonly ClientApplicationValidator $validator,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $this->can($r, 'integration.manage');
        $page = (int) ($r->query()['page'] ?? 1);
        $perPage = (int) ($r->query()['per_page'] ?? 25);
        $result = $this->service->list(max(1, $page), max(1, min(100, $perPage)));

        return new Response(200, [
            'data' => array_map(
                static fn($a) => ['type' => 'client-applications', 'id' => (string) $a->id(), 'attributes' => $a->toArray()],
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
        $this->can($r, 'integration.manage');
        $app = $this->service->get($this->id($r));

        return ApiResponse::resource('client-applications', (string) $app->id(), $app->toArray());
    }

    public function store(Request $r): Response
    {
        $userId = $this->can($r, 'integration.manage');
        $data = CreateClientApplicationData::fromArray($r->body());
        $this->validator->create($data);
        $app = $this->service->create($userId, $data);

        return new Response(201, [
            'data' => ['type' => 'client-applications', 'id' => (string) $app->id(), 'attributes' => $app->toArray()],
        ]);
    }

    public function update(Request $r): Response
    {
        $userId = $this->can($r, 'integration.manage');
        $data = UpdateClientApplicationData::fromArray($r->body());
        $app = $this->service->update($this->id($r), $userId, $data);

        return ApiResponse::resource('client-applications', (string) $app->id(), $app->toArray());
    }

    public function destroy(Request $r): Response
    {
        $this->can($r, 'integration.manage');
        $this->service->delete($this->id($r));

        return ApiResponse::noContent();
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
