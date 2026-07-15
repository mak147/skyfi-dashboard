<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Workflow\Contracts\WorkflowServiceContract;

final class WorkflowDashboardController
{
    public function __construct(
        private readonly WorkflowServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function show(Request $request): Response
    {
        $this->can($request, 'workflow.view');
        $data = $this->service->dashboard();

        return ApiResponse::resource('workflow-dashboard', 'summary', $data);
    }

    public function tick(Request $request): Response
    {
        $this->can($request, 'workflow.manage');
        $processed = $this->service->processScheduler();

        return new Response(200, [
            'data' => [
                'type' => 'workflow-scheduler',
                'id' => 'tick',
                'attributes' => ['processed' => $processed],
            ],
        ]);
    }

    private function can(Request $request, string $permission): void
    {
        $claims = $request->attributes()['claims'] ?? [];
        $userId = (int) ($claims['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);
    }
}
