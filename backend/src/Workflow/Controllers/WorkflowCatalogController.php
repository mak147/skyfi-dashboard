<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Workflow\Contracts\WorkflowServiceContract;
use SkyFi\Workflow\Services\WorkflowCatalog;

final class WorkflowCatalogController
{
    public function __construct(
        private readonly WorkflowServiceContract $service,
        private readonly WorkflowCatalog $catalog,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function all(Request $request): Response
    {
        $this->can($request);
        $data = $this->service->catalogs();

        return new Response(200, [
            'data' => [
                'type' => 'workflow-catalogs',
                'id' => 'default',
                'attributes' => $data,
            ],
        ]);
    }

    public function triggers(Request $request): Response
    {
        $this->can($request);
        $data = $this->service->catalogs();

        return new Response(200, [
            'data' => array_map(
                static function (array $event, int $index): array {
                    return [
                        'type' => 'workflow-triggers',
                        'id' => (string) ($event['id'] ?? $event['event_key'] ?? $index),
                        'attributes' => $event,
                    ];
                },
                $data['triggers'] ?? [],
                array_keys($data['triggers'] ?? []),
            ),
        ]);
    }

    public function actions(Request $request): Response
    {
        $this->can($request);

        return new Response(200, [
            'data' => array_map(
                static fn (array $action): array => [
                    'type' => 'workflow-actions',
                    'id' => (string) $action['type'],
                    'attributes' => $action,
                ],
                $this->catalog->actions(),
            ),
        ]);
    }

    public function operators(Request $request): Response
    {
        $this->can($request);

        return new Response(200, [
            'data' => array_map(
                static fn (array $op): array => [
                    'type' => 'workflow-operators',
                    'id' => (string) $op['id'],
                    'attributes' => $op,
                ],
                $this->catalog->operators(),
            ),
        ]);
    }

    private function can(Request $request): void
    {
        $claims = $request->attributes()['claims'] ?? [];
        $userId = (int) ($claims['sub'] ?? 0);
        $this->auth->authorize($userId, 'workflow.view');
    }
}
