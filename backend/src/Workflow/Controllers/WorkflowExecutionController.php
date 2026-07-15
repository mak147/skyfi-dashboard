<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Workflow\Contracts\WorkflowServiceContract;
use SkyFi\Workflow\DTOs\ExecutionListFilters;

final class WorkflowExecutionController
{
    public function __construct(
        private readonly WorkflowServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $request): Response
    {
        $this->can($request, 'workflow.view');
        $filters = ExecutionListFilters::fromQuery($request->query());
        $params = $request->attributes()['route_params'] ?? $request->attributes();
        if (isset($params['id']) && (int) $params['id'] > 0) {
            $filters = new ExecutionListFilters(
                workflowId: (int) $params['id'],
                status: $filters->status,
                triggerEventKey: $filters->triggerEventKey,
                triggerSource: $filters->triggerSource,
                search: $filters->search,
                from: $filters->from,
                to: $filters->to,
                page: $filters->page,
                perPage: $filters->perPage,
            );
        }
        $result = $this->service->executions($filters);

        return new Response(200, [
            'data' => array_map(
                static fn ($e) => ['type' => 'workflow-executions', 'id' => (string) $e->id(), 'attributes' => $e->toArray()],
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

    public function show(Request $request): Response
    {
        $this->can($request, 'workflow.view');
        $execution = $this->service->execution($this->executionId($request));

        return ApiResponse::resource('workflow-executions', (string) $execution->id(), $execution->toArray());
    }

    public function retry(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.execute');
        $execution = $this->service->retryExecution($this->executionId($request), $userId);

        return ApiResponse::resource('workflow-executions', (string) $execution->id(), $execution->toArray());
    }

    public function cancel(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.manage');
        $execution = $this->service->cancelExecution($this->executionId($request), $userId);

        return ApiResponse::resource('workflow-executions', (string) $execution->id(), $execution->toArray());
    }

    public function pause(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.manage');
        $execution = $this->service->pauseExecution($this->executionId($request), $userId);

        return ApiResponse::resource('workflow-executions', (string) $execution->id(), $execution->toArray());
    }

    public function resume(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.manage');
        $execution = $this->service->resumeExecution($this->executionId($request), $userId);

        return ApiResponse::resource('workflow-executions', (string) $execution->id(), $execution->toArray());
    }

    private function executionId(Request $request): int
    {
        $params = $request->attributes()['route_params'] ?? $request->attributes();

        return (int) ($params['executionId'] ?? $params['id'] ?? 0);
    }

    private function can(Request $request, string $permission): int
    {
        $claims = $request->attributes()['claims'] ?? [];
        $userId = (int) ($claims['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);

        return $userId;
    }
}
