<?php

declare(strict_types=1);

use SkyFi\Connections\Controllers\ConnectionController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(ConnectionController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $router->add('GET', '/api/v1/connections', ProtectRoute::wrap($auth, $controller->index(...)));
    $router->add('POST', '/api/v1/connections', ProtectRoute::wrap($auth, $controller->store(...)));
    $router->add('GET', '/api/v1/connections/{id}', ProtectRoute::wrap($auth, $controller->show(...)));
    $router->add('PUT', '/api/v1/connections/{id}', ProtectRoute::wrap($auth, $controller->update(...)));
    $router->add('DELETE', '/api/v1/connections/{id}', ProtectRoute::wrap($auth, $controller->destroy(...)));
    
    $router->add('PATCH', '/api/v1/connections/{id}/activate', ProtectRoute::wrap($auth, $controller->activate(...)));
    $router->add('PATCH', '/api/v1/connections/{id}/suspend', ProtectRoute::wrap($auth, $controller->suspend(...)));
    $router->add('PATCH', '/api/v1/connections/{id}/disconnect', ProtectRoute::wrap($auth, $controller->disconnect(...)));
    $router->add('PATCH', '/api/v1/connections/{id}/transfer', ProtectRoute::wrap($auth, $controller->transfer(...)));
};
