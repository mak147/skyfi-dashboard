<?php

declare(strict_types=1);

use SkyFi\Rbac\Controllers\RbacController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(RbacController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $router->add('GET', '/api/v1/me/permissions', ProtectRoute::wrap($auth, $controller->getEffectivePermissions(...)));
    $router->add('GET', '/api/v1/roles', ProtectRoute::wrap($auth, $controller->getAllRoles(...)));
    $router->add('POST', '/api/v1/roles', ProtectRoute::wrap($auth, $controller->createRole(...)));
    $router->add('GET', '/api/v1/roles/{id}', ProtectRoute::wrap($auth, $controller->getRole(...)));
    $router->add('PUT', '/api/v1/roles/{id}', ProtectRoute::wrap($auth, $controller->updateRole(...)));
    $router->add('DELETE', '/api/v1/roles/{id}', ProtectRoute::wrap($auth, $controller->deleteRole(...)));
    
    $router->add('GET', '/api/v1/permissions', ProtectRoute::wrap($auth, $controller->getAllPermissions(...)));
    
    $router->add('GET', '/api/v1/users/{id}/roles', ProtectRoute::wrap($auth, $controller->getUserRoles(...)));
    $router->add('PUT', '/api/v1/users/{id}/roles', ProtectRoute::wrap($auth, $controller->syncUserRoles(...)));
};
