<?php

declare(strict_types=1);

use SkyFi\Customers\Controllers\CustomerController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(CustomerController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $router->add('GET', '/api/v1/customers', ProtectRoute::wrap($auth, $controller->index(...)));
    $router->add('POST', '/api/v1/customers', ProtectRoute::wrap($auth, $controller->store(...)));
    $router->add('GET', '/api/v1/customers/{id}', ProtectRoute::wrap($auth, $controller->show(...)));
    $router->add('PUT', '/api/v1/customers/{id}', ProtectRoute::wrap($auth, $controller->update(...)));
    $router->add('DELETE', '/api/v1/customers/{id}', ProtectRoute::wrap($auth, $controller->destroy(...)));
    $router->add('PATCH', '/api/v1/customers/{id}/status', ProtectRoute::wrap($auth, $controller->changeStatus(...)));
};
