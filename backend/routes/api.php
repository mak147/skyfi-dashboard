<?php

declare(strict_types=1);

use SkyFi\Shared\Auth\Controllers\AuthController;
use SkyFi\Shared\Http\Router;

/**
 * Registers API v1 routes. Authentication is the only module enabled in this
 * first implementation slice; business module routes will be added separately.
 */
return static function (Router $router, AuthController $controller): void {
    $registerAuthRoutes = require __DIR__ . '/auth.php';
    $registerAuthRoutes($router, $controller);
};
