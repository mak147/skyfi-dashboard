<?php

declare(strict_types=1);

use SkyFi\Rbac\Controllers\RbacController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;
use SkyFi\Shared\Http\Request;

return static function (Router $router, Container $container): void {
    $controller = $container->get(RbacController::class);
    $authMiddleware = $container->get(JwtAuthMiddleware::class);

    $protect = function (callable $handler) use ($authMiddleware) {
        return function (Request $request) use ($handler, $authMiddleware) {
            $claims = $authMiddleware->authenticate($request);
            $attributes = $request->attributes();
            $attributes['claims'] = $claims;
            $request = $request->withAttributes($attributes);
            return $handler($request);
        };
    };

    $router->add('GET', '/api/v1/roles', $protect($controller->getAllRoles(...)));
    $router->add('POST', '/api/v1/roles', $protect($controller->createRole(...)));
    $router->add('GET', '/api/v1/roles/{id}', $protect($controller->getRole(...)));
    $router->add('PUT', '/api/v1/roles/{id}', $protect($controller->updateRole(...)));
    $router->add('DELETE', '/api/v1/roles/{id}', $protect($controller->deleteRole(...)));
    
    $router->add('GET', '/api/v1/permissions', $protect($controller->getAllPermissions(...)));
    
    $router->add('GET', '/api/v1/users/{id}/roles', $protect($controller->getUserRoles(...)));
    $router->add('PUT', '/api/v1/users/{id}/roles', $protect($controller->syncUserRoles(...)));
};
