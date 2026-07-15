<?php

declare(strict_types=1);

use SkyFi\Shared\Auth\Controllers\AuthController;
use SkyFi\Shared\Http\Router;

return static function (Router $router, AuthController $controller): void {
    $router->add('POST', '/api/v1/auth/login', $controller->login(...));
    $router->add('POST', '/api/v1/auth/refresh', $controller->refresh(...));
    $router->add('POST', '/api/v1/auth/logout', $controller->logout(...));
    $router->add('POST', '/api/v1/auth/forgot-password', $controller->forgotPassword(...));
    $router->add('POST', '/api/v1/auth/reset-password', $controller->resetPassword(...));
    $router->add('POST', '/api/v1/auth/change-password', $controller->changePassword(...));
};
