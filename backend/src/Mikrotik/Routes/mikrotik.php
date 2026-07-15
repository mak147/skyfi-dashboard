<?php

declare(strict_types=1);

use SkyFi\Mikrotik\Controllers\RouterController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(RouterController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $router->add('GET', '/api/v1/mikrotik/routers', ProtectRoute::wrap($auth, $controller->index(...)));
    $router->add('POST', '/api/v1/mikrotik/routers', ProtectRoute::wrap($auth, $controller->store(...)));
    $router->add('POST', '/api/v1/mikrotik/test-connection', ProtectRoute::wrap($auth, $controller->testTransient(...)));

    $router->add('GET', '/api/v1/mikrotik/routers/{id}', ProtectRoute::wrap($auth, $controller->show(...)));
    $router->add('PUT', '/api/v1/mikrotik/routers/{id}', ProtectRoute::wrap($auth, $controller->update(...)));
    $router->add('DELETE', '/api/v1/mikrotik/routers/{id}', ProtectRoute::wrap($auth, $controller->destroy(...)));
    $router->add('PATCH', '/api/v1/mikrotik/routers/{id}/enable', ProtectRoute::wrap($auth, $controller->enable(...)));
    $router->add('PATCH', '/api/v1/mikrotik/routers/{id}/disable', ProtectRoute::wrap($auth, $controller->disable(...)));
    $router->add('PUT', '/api/v1/mikrotik/routers/{id}/tags', ProtectRoute::wrap($auth, $controller->syncTags(...)));
    $router->add('POST', '/api/v1/mikrotik/routers/{id}/test-connection', ProtectRoute::wrap($auth, $controller->testSaved(...)));
    $router->add('POST', '/api/v1/mikrotik/routers/{id}/discover', ProtectRoute::wrap($auth, $controller->discover(...)));
    $router->add('GET', '/api/v1/mikrotik/routers/{id}/health', ProtectRoute::wrap($auth, $controller->latestHealth(...)));
    $router->add('POST', '/api/v1/mikrotik/routers/{id}/health/check', ProtectRoute::wrap($auth, $controller->checkHealth(...)));
    $router->add('GET', '/api/v1/mikrotik/routers/{id}/statistics', ProtectRoute::wrap($auth, $controller->statistics(...)));

    $router->add('GET', '/api/v1/mikrotik/router-groups', ProtectRoute::wrap($auth, $controller->groups(...)));
    $router->add('POST', '/api/v1/mikrotik/router-groups', ProtectRoute::wrap($auth, $controller->createGroup(...)));
    $router->add('PUT', '/api/v1/mikrotik/router-groups/{id}', ProtectRoute::wrap($auth, $controller->updateGroup(...)));
    $router->add('DELETE', '/api/v1/mikrotik/router-groups/{id}', ProtectRoute::wrap($auth, $controller->deleteGroup(...)));

    $router->add('GET', '/api/v1/mikrotik/router-tags', ProtectRoute::wrap($auth, $controller->tags(...)));
    $router->add('POST', '/api/v1/mikrotik/router-tags', ProtectRoute::wrap($auth, $controller->createTag(...)));
    $router->add('PUT', '/api/v1/mikrotik/router-tags/{id}', ProtectRoute::wrap($auth, $controller->updateTag(...)));
    $router->add('DELETE', '/api/v1/mikrotik/router-tags/{id}', ProtectRoute::wrap($auth, $controller->deleteTag(...)));
};
