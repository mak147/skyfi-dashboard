<?php

declare(strict_types=1);

use SkyFi\Mikrotik\Controllers\RouterController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(RouterController::class);
    $auth = $container->get(JwtAuthMiddleware::class);
    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    $router->add('GET', '/api/v1/mikrotik/routers', $protect($controller->index(...)));
    $router->add('POST', '/api/v1/mikrotik/routers', $protect($controller->store(...)));
    $router->add('POST', '/api/v1/mikrotik/test-connection', $protect($controller->testTransient(...)));

    $router->add('GET', '/api/v1/mikrotik/routers/{id}', $protect($controller->show(...)));
    $router->add('PUT', '/api/v1/mikrotik/routers/{id}', $protect($controller->update(...)));
    $router->add('DELETE', '/api/v1/mikrotik/routers/{id}', $protect($controller->destroy(...)));
    $router->add('PATCH', '/api/v1/mikrotik/routers/{id}/enable', $protect($controller->enable(...)));
    $router->add('PATCH', '/api/v1/mikrotik/routers/{id}/disable', $protect($controller->disable(...)));
    $router->add('PUT', '/api/v1/mikrotik/routers/{id}/tags', $protect($controller->syncTags(...)));
    $router->add('POST', '/api/v1/mikrotik/routers/{id}/test-connection', $protect($controller->testSaved(...)));
    $router->add('POST', '/api/v1/mikrotik/routers/{id}/discover', $protect($controller->discover(...)));
    $router->add('GET', '/api/v1/mikrotik/routers/{id}/health', $protect($controller->latestHealth(...)));
    $router->add('POST', '/api/v1/mikrotik/routers/{id}/health/check', $protect($controller->checkHealth(...)));
    $router->add('GET', '/api/v1/mikrotik/routers/{id}/statistics', $protect($controller->statistics(...)));

    $router->add('GET', '/api/v1/mikrotik/router-groups', $protect($controller->groups(...)));
    $router->add('POST', '/api/v1/mikrotik/router-groups', $protect($controller->createGroup(...)));
    $router->add('PUT', '/api/v1/mikrotik/router-groups/{id}', $protect($controller->updateGroup(...)));
    $router->add('DELETE', '/api/v1/mikrotik/router-groups/{id}', $protect($controller->deleteGroup(...)));

    $router->add('GET', '/api/v1/mikrotik/router-tags', $protect($controller->tags(...)));
    $router->add('POST', '/api/v1/mikrotik/router-tags', $protect($controller->createTag(...)));
    $router->add('PUT', '/api/v1/mikrotik/router-tags/{id}', $protect($controller->updateTag(...)));
    $router->add('DELETE', '/api/v1/mikrotik/router-tags/{id}', $protect($controller->deleteTag(...)));
};
