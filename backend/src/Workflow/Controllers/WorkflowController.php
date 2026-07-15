<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Workflow\Contracts\WorkflowServiceContract;
use SkyFi\Workflow\DTOs\CreateWorkflowData;
use SkyFi\Workflow\DTOs\RunWorkflowData;
use SkyFi\Workflow\DTOs\UpdateWorkflowData;
use SkyFi\Workflow\DTOs\WorkflowListFilters;
use SkyFi\Workflow\Validators\WorkflowValidator;

final class WorkflowController
{
    public function __construct(
        private readonly WorkflowServiceContract $service,
        private readonly WorkflowValidator $validator,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $request): Response
    {
        $this->can($request, 'workflow.view');
        $result = $this->service->list(WorkflowListFilters::fromQuery($request->query()));

        return new Response(200, [
            'data' => array_map(
                static fn ($w) => ['type' => 'workflows', 'id' => (string) $w->id(), 'attributes' => $w->toArray()],
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
        $detail = $this->service->get($this->id($request));

        return new Response(200, [
            'data' => [
                'type' => 'workflows',
                'id' => (string) $detail['workflow']['id'],
                'attributes' => $detail,
            ],
        ]);
    }

    public function store(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.create');
        $data = CreateWorkflowData::fromArray($request->body());
        $this->validator->create($data);
        $workflow = $this->service->create($userId, $data);

        return ApiResponse::resource('workflows', (string) $workflow->id(), $workflow->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.update');
        $data = UpdateWorkflowData::fromArray($request->body());
        $this->validator->update($data);
        $workflow = $this->service->update($this->id($request), $userId, $data);

        return ApiResponse::resource('workflows', (string) $workflow->id(), $workflow->toArray());
    }

    public function destroy(Request $request): Response
    {
        $this->can($request, 'workflow.manage');
        $this->service->delete($this->id($request));

        return ApiResponse::noContent();
    }

    public function enable(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.update');
        $workflow = $this->service->enable($this->id($request), $userId);

        return ApiResponse::resource('workflows', (string) $workflow->id(), $workflow->toArray());
    }

    public function disable(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.update');
        $workflow = $this->service->disable($this->id($request), $userId);

        return ApiResponse::resource('workflows', (string) $workflow->id(), $workflow->toArray());
    }

    public function pause(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.manage');
        $workflow = $this->service->pause($this->id($request), $userId);

        return ApiResponse::resource('workflows', (string) $workflow->id(), $workflow->toArray());
    }

    public function resume(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.manage');
        $workflow = $this->service->resume($this->id($request), $userId);

        return ApiResponse::resource('workflows', (string) $workflow->id(), $workflow->toArray());
    }

    public function cloneWorkflow(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.create');
        $workflow = $this->service->cloneWorkflow($this->id($request), $userId);

        return ApiResponse::resource('workflows', (string) $workflow->id(), $workflow->toArray(), 201);
    }

    public function versions(Request $request): Response
    {
        $this->can($request, 'workflow.view');
        $versions = $this->service->versions($this->id($request));

        return new Response(200, [
            'data' => array_map(
                static fn ($v) => ['type' => 'workflow-versions', 'id' => (string) $v->id(), 'attributes' => $v->toArray()],
                $versions,
            ),
        ]);
    }

    public function version(Request $request): Response
    {
        $this->can($request, 'workflow.view');
        $params = $request->attributes()['route_params'] ?? $request->attributes();
        $versionId = (int) ($params['versionId'] ?? $params['version_id'] ?? 0);
        $version = $this->service->version($this->id($request), $versionId);

        return ApiResponse::resource('workflow-versions', (string) $version->id(), $version->toArray());
    }

    public function run(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.execute');
        $execution = $this->service->run($this->id($request), $userId, RunWorkflowData::fromArray($request->body()));

        return ApiResponse::resource('workflow-executions', (string) $execution->id(), $execution->toArray(), 201);
    }

    public function test(Request $request): Response
    {
        $userId = $this->can($request, 'workflow.execute');
        $execution = $this->service->test($this->id($request), $userId, RunWorkflowData::fromArray($request->body()));

        return ApiResponse::resource('workflow-executions', (string) $execution->id(), $execution->toArray(), 201);
    }

    private function id(Request $request): int
    {
        $params = $request->attributes()['route_params'] ?? $request->attributes();

        return (int) ($params['id'] ?? 0);
    }

    private function can(Request $request, string $permission): int
    {
        $claims = $request->attributes()['claims'] ?? [];
        $userId = (int) ($claims['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);

        return $userId;
    }
}
