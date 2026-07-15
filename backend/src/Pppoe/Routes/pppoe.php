<?php

declare(strict_types=1);

use SkyFi\Pppoe\Controllers\PppoeAccountController;
use SkyFi\Pppoe\Controllers\PppoeSessionController;
use SkyFi\Pppoe\Controllers\PppoeSyncController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $accountController = $container->get(PppoeAccountController::class);
    $sessionController = $container->get(PppoeSessionController::class);
    $syncController = $container->get(PppoeSyncController::class);

    $auth = $container->get(JwtAuthMiddleware::class);

    // PPPoE Account Management
    $router->add('GET', '/api/v1/pppoe/accounts', ProtectRoute::wrap($auth, $accountController->index(...)));
    $router->add('POST', '/api/v1/pppoe/accounts', ProtectRoute::wrap($auth, $accountController->store(...)));
    $router->add('GET', '/api/v1/pppoe/accounts/{id}', ProtectRoute::wrap($auth, $accountController->show(...)));
    $router->add('PUT', '/api/v1/pppoe/accounts/{id}', ProtectRoute::wrap($auth, $accountController->update(...)));
    $router->add('DELETE', '/api/v1/pppoe/accounts/{id}', ProtectRoute::wrap($auth, $accountController->destroy(...)));

    $router->add('PATCH', '/api/v1/pppoe/accounts/{id}/enable', ProtectRoute::wrap($auth, $accountController->enable(...)));
    $router->add('PATCH', '/api/v1/pppoe/accounts/{id}/disable', ProtectRoute::wrap($auth, $accountController->disable(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/suspend', ProtectRoute::wrap($auth, $accountController->suspend(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/resume', ProtectRoute::wrap($auth, $accountController->resume(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/reconnect', ProtectRoute::wrap($auth, $accountController->reconnect(...)));
    $router->add('POST', '/api/v1/pppoe/accounts/{id}/reset-password', ProtectRoute::wrap($auth, $accountController->resetPassword(...)));
    $router->add('PUT', '/api/v1/pppoe/accounts/{id}/package', ProtectRoute::wrap($auth, $accountController->changePackage(...)));

    // PPPoE Active Session Management & Monitoring
    $router->add('GET', '/api/v1/pppoe/sessions/active', ProtectRoute::wrap($auth, $sessionController->activeSessions(...)));
    $router->add('POST', '/api/v1/pppoe/sessions/active/disconnect', ProtectRoute::wrap($auth, $sessionController->disconnectSession(...)));
    $router->add('GET', '/api/v1/pppoe/accounts/{id}/sessions/history', ProtectRoute::wrap($auth, $sessionController->sessionHistory(...)));
    $router->add('GET', '/api/v1/pppoe/sessions/history', ProtectRoute::wrap($auth, $sessionController->sessionHistory(...)));
    $router->add('GET', '/api/v1/pppoe/accounts/{id}/statistics', ProtectRoute::wrap($auth, $sessionController->statistics(...)));

    // PPPoE Synchronization & Router Interaction
    $router->add('POST', '/api/v1/pppoe/sync/router/{routerId}', ProtectRoute::wrap($auth, $syncController->syncRouter(...)));
    $router->add('POST', '/api/v1/pppoe/sync/account/{id}', ProtectRoute::wrap($auth, $syncController->syncAccount(...)));
    $router->add('POST', '/api/v1/pppoe/sync/detect-missing', ProtectRoute::wrap($auth, $syncController->detectMissing(...)));
    $router->add('POST', '/api/v1/pppoe/sync/repair', ProtectRoute::wrap($auth, $syncController->repair(...)));
    $router->add('POST', '/api/v1/pppoe/sync/import', ProtectRoute::wrap($auth, $syncController->importUsers(...)));
    $router->add('GET', '/api/v1/pppoe/routers/{routerId}/profiles', ProtectRoute::wrap($auth, $syncController->routerProfiles(...)));
    $router->add('GET', '/api/v1/pppoe/sync/logs', ProtectRoute::wrap($auth, $syncController->syncLogs(...)));
};
