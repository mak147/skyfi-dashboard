<?php

declare(strict_types=1);

use SkyFi\Shared\Auth\Controllers\AuthController;
use SkyFi\Shared\Http\Middleware\RateLimitMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Shared\Http\Router;

return static function (Router $router, AuthController $controller, RateLimitMiddleware $rateLimiter): void {
    $rateLimit = static function (callable $handler) use ($rateLimiter): callable {
        return static function (Request $request) use ($handler, $rateLimiter): Response {
            $rateLimiter->check($request);
            return $handler($request);
        };
    };

    $router->add('POST', '/api/v1/auth/login', $rateLimit($controller->login(...)));
    $router->add('POST', '/api/v1/auth/refresh', $controller->refresh(...));
    $router->add('POST', '/api/v1/auth/logout', $controller->logout(...));
    $router->add('POST', '/api/v1/auth/forgot-password', $rateLimit($controller->forgotPassword(...)));
    $router->add('POST', '/api/v1/auth/reset-password', $rateLimit($controller->resetPassword(...)));
    $router->add('POST', '/api/v1/auth/change-password', $controller->changePassword(...));
};
