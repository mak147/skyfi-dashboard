<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;
use SkyFi\Workflow\Controllers\WorkflowCatalogController;
use SkyFi\Workflow\Controllers\WorkflowController;
use SkyFi\Workflow\Controllers\WorkflowDashboardController;
use SkyFi\Workflow\Controllers\WorkflowExecutionController;

return static function (Router $router, Container $container): void {
    $workflows = $container->get(WorkflowController::class);
    $executions = $container->get(WorkflowExecutionController::class);
    $catalog = $container->get(WorkflowCatalogController::class);
    $dashboard = $container->get(WorkflowDashboardController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // Dashboard & catalogs (static paths first)
    $router->add('GET', '/api/v1/workflows/dashboard', $protect($dashboard->show(...)));
    $router->add('POST', '/api/v1/workflows/scheduler/tick', $protect($dashboard->tick(...)));
    $router->add('GET', '/api/v1/workflows/catalog', $protect($catalog->all(...)));
    $router->add('GET', '/api/v1/workflows/triggers/catalog', $protect($catalog->triggers(...)));
    $router->add('GET', '/api/v1/workflows/actions/catalog', $protect($catalog->actions(...)));
    $router->add('GET', '/api/v1/workflows/operators', $protect($catalog->operators(...)));

    // Global executions
    $router->add('GET', '/api/v1/workflows/executions', $protect($executions->index(...)));
    $router->add('GET', '/api/v1/workflows/executions/{executionId}', $protect($executions->show(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/retry', $protect($executions->retry(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/cancel', $protect($executions->cancel(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/pause', $protect($executions->pause(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/resume', $protect($executions->resume(...)));

    // Workflow CRUD
    $router->add('GET', '/api/v1/workflows', $protect($workflows->index(...)));
    $router->add('POST', '/api/v1/workflows', $protect($workflows->store(...)));
    $router->add('GET', '/api/v1/workflows/{id}', $protect($workflows->show(...)));
    $router->add('PUT', '/api/v1/workflows/{id}', $protect($workflows->update(...)));
    $router->add('DELETE', '/api/v1/workflows/{id}', $protect($workflows->destroy(...)));

    // Lifecycle
    $router->add('POST', '/api/v1/workflows/{id}/enable', $protect($workflows->enable(...)));
    $router->add('POST', '/api/v1/workflows/{id}/disable', $protect($workflows->disable(...)));
    $router->add('POST', '/api/v1/workflows/{id}/pause', $protect($workflows->pause(...)));
    $router->add('POST', '/api/v1/workflows/{id}/resume', $protect($workflows->resume(...)));
    $router->add('POST', '/api/v1/workflows/{id}/clone', $protect($workflows->cloneWorkflow(...)));
    $router->add('POST', '/api/v1/workflows/{id}/run', $protect($workflows->run(...)));
    $router->add('POST', '/api/v1/workflows/{id}/test', $protect($workflows->test(...)));

    // Versions & per-workflow executions
    $router->add('GET', '/api/v1/workflows/{id}/versions', $protect($workflows->versions(...)));
    $router->add('GET', '/api/v1/workflows/{id}/versions/{versionId}', $protect($workflows->version(...)));
    $router->add('GET', '/api/v1/workflows/{id}/executions', $protect($executions->index(...)));
};
