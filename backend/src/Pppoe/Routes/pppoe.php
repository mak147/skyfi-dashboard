<?php

declare(strict_types=1);

use SkyFi\Pppoe\Controllers\PppoeAccountController;
use SkyFi\Pppoe\Controllers\PppoeSessionController;
use SkyFi\Pppoe\Controllers\PppoeSyncController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $accountController = $container->get(PppoeAccountController::class);
    $sessionController = $container->get(PppoeSessionController::class);
    $syncController = $container->get(PppoeSyncController::class);

    $auth = $container->get(JwtAuthMiddleware::class);
    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // PPPoE Account Management
    $router->add('GET', '/api/v1/pppoe/accounts', $protect($accountController->index(...)));
    $router->add('POST', '/api/v1/pppoe/accounts', $protect($accountController->store(...)));
    $router->add('GET', '/api/v1/pppoe/accounts/{id}', $protect($accountController->show(...)));
    $router->add('PUT', '/api/v1/pppoe/accounts/{id}', $protect($accountController->update(...)));
    $router->add('DELETE', '/api/v1/pppoe/accounts/{id}', $protect($accountController->destroy(...)));

    $router->add('PATCH', '/api/v1/pppoe/accounts/{id}/enable', $protect($accountController->enable(...)));
    $router->add('PATCH', '/api/v1/pppoe/accounts/{id}/disable', $protect($accountController->disable(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/suspend', $protect($accountController->suspend(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/resume', $protect($accountController->resume(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/reconnect', $protect($accountController->reconnect(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/reset-password', $protect($accountController->resetPassword(...)));
    $router->add('PUT', '/api/v1/pppoe/accounts/{id}/package', $protect($accountController->changePackage(...)));

    // PPPoE Active Session Management & Monitoring
    $router->add('GET', '/api/v1/pppoe/sessions/active', $protect($sessionController->activeSessions(...)));
    $router->add('POST', '/api/v1/pppoe/sessions/active/disconnect', $protect($sessionController->disconnectSession(...)));
    $router->add('GET', '/api/v1/pppoe/accounts/{id}/sessions/history', $protect($sessionController->sessionHistory(...)));
    $router->add('GET', '/api/v1/pppoe/sessions/history', $protect($sessionController->sessionHistory(...)));
    $router->add('GET', '/api/v1/pppoe/accounts/{id}/statistics', $protect($sessionController->statistics(...)));

    // PPPoE Synchronization & Router Interaction
    $router->add('POST', '/api/v1/pppoe/sync/router/{routerId}', $protect($syncController->syncRouter(...)));
    $router->add('POST', '/api/v1/pppoe/sync/account/{id}', $protect($syncController->syncAccount(...)));
    $router->add('POST', '/api/v1/pppoe/sync/detect-missing', $protect($syncController->detectMissing(...)));
    $router->add('POST', '/api/v1/pppoe/sync/repair', $protect($syncController->repair(...)));
    $router->add('POST', '/api/v1/pppoe/sync/import', $protect($syncController->importUsers(...)));
    $router->add('GET', '/api/v1/pppoe/routers/{routerId}/profiles', $protect($syncController->routerProfiles(...)));
    $router->add('GET', '/api/v1/pppoe/sync/logs', $protect($syncController->syncLogs(...)));
};
