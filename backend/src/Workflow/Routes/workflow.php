<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
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

    // Dashboard & catalogs (static paths first)
    $router->add('GET', '/api/v1/workflows/dashboard', ProtectRoute::wrap($auth, $dashboard->show(...)));
    $router->add('POST', '/api/v1/workflows/scheduler/tick', ProtectRoute::wrap($auth, $dashboard->tick(...)));
    $router->add('GET', '/api/v1/workflows/catalog', ProtectRoute::wrap($auth, $catalog->all(...)));
    $router->add('GET', '/api/v1/workflows/triggers/catalog', ProtectRoute::wrap($auth, $catalog->triggers(...)));
    $router->add('GET', '/api/v1/workflows/actions/catalog', ProtectRoute::wrap($auth, $catalog->actions(...)));
    $router->add('GET', '/api/v1/workflows/operators', ProtectRoute::wrap($auth, $catalog->operators(...)));

    // Global executions
    $router->add('GET', '/api/v1/workflows/executions', ProtectRoute::wrap($auth, $executions->index(...)));
    $router->add('GET', '/api/v1/workflows/executions/{executionId}', ProtectRoute::wrap($auth, $executions->show(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/retry', ProtectRoute::wrap($auth, $executions->retry(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/cancel', ProtectRoute::wrap($auth, $executions->cancel(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/pause', ProtectRoute::wrap($auth, $executions->pause(...)));
    $router->add('POST', '/api/v1/workflows/executions/{executionId}/resume', ProtectRoute::wrap($auth, $executions->resume(...)));

    // Workflow CRUD
    $router->add('GET', '/api/v1/workflows', ProtectRoute::wrap($auth, $workflows->index(...)));
    $router->add('POST', '/api/v1/workflows', ProtectRoute::wrap($auth, $workflows->store(...)));
    $router->add('GET', '/api/v1/workflows/{id}', ProtectRoute::wrap($auth, $workflows->show(...)));
    $router->add('PUT', '/api/v1/workflows/{id}', ProtectRoute::wrap($auth, $workflows->update(...)));
    $router->add('DELETE', '/api/v1/workflows/{id}', ProtectRoute::wrap($auth, $workflows->destroy(...)));

    // Lifecycle
    $router->add('POST', '/api/v1/workflows/{id}/enable', ProtectRoute::wrap($auth, $workflows->enable(...)));
    $router->add('POST', '/api/v1/workflows/{id}/disable', ProtectRoute::wrap($auth, $workflows->disable(...)));
    $router->add('POST', '/api/v1/workflows/{id}/pause', ProtectRoute::wrap($auth, $workflows->pause(...)));
    $router->add('POST', '/api/v1/workflows/{id}/resume', ProtectRoute::wrap($auth, $workflows->resume(...)));
    $router->add('POST', '/api/v1/workflows/{id}/clone', ProtectRoute::wrap($auth, $workflows->cloneWorkflow(...)));
    $router->add('POST', '/api/v1/workflows/{id}/run', ProtectRoute::wrap($auth, $workflows->run(...)));
    $router->add('POST', '/api/v1/workflows/{id}/test', ProtectRoute::wrap($auth, $workflows->test(...)));

    // Versions & per-workflow executions
    $router->add('GET', '/api/v1/workflows/{id}/versions', ProtectRoute::wrap($auth, $workflows->versions(...)));
    $router->add('GET', '/api/v1/workflows/{id}/versions/{versionId}', ProtectRoute::wrap($auth, $workflows->version(...)));
    $router->add('GET', '/api/v1/workflows/{id}/executions', ProtectRoute::wrap($auth, $executions->index(...)));
};
