<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $registerAuthRoutes = require __DIR__ . '/auth.php';
    $registerAuthRoutes($router, $container->get(\SkyFi\Shared\Auth\Controllers\AuthController::class));

    $registerRbacRoutes = require __DIR__ . '/rbac.php';
    $registerRbacRoutes($router, $container);
};
