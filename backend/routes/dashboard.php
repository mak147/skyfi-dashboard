<?php

declare(strict_types=1);

use SkyFi\Dashboard\Controllers\DashboardController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(DashboardController::class);
    $authMiddleware = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($authMiddleware): callable {
        return static function (Request $request) use ($handler, $authMiddleware) {
            $claims = $authMiddleware->authenticate($request);
            $attributes = $request->attributes();
            $attributes['claims'] = $claims;

            return $handler($request->withAttributes($attributes));
        };
    };

    $router->add('GET', '/api/v1/dashboard', $protect($controller->show(...)));
};
