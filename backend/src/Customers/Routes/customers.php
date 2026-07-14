<?php

declare(strict_types=1);

use SkyFi\Customers\Controllers\CustomerController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(CustomerController::class);
    $authMiddleware = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($authMiddleware): callable {
        return static function (Request $request) use ($handler, $authMiddleware) {
            $claims = $authMiddleware->authenticate($request);
            $attributes = $request->attributes();
            $attributes['claims'] = $claims;

            return $handler($request->withAttributes($attributes));
        };
    };

    $router->add('GET', '/api/v1/customers', $protect($controller->index(...)));
    $router->add('POST', '/api/v1/customers', $protect($controller->store(...)));
    $router->add('GET', '/api/v1/customers/{id}', $protect($controller->show(...)));
    $router->add('PUT', '/api/v1/customers/{id}', $protect($controller->update(...)));
    $router->add('DELETE', '/api/v1/customers/{id}', $protect($controller->destroy(...)));
    $router->add('PATCH', '/api/v1/customers/{id}/status', $protect($controller->changeStatus(...)));
};
