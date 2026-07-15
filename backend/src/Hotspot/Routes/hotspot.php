<?php

declare(strict_types=1);

use SkyFi\Hotspot\Controllers\HotspotProfileController;
use SkyFi\Hotspot\Controllers\HotspotSessionController;
use SkyFi\Hotspot\Controllers\HotspotSyncController;
use SkyFi\Hotspot\Controllers\HotspotUserController;
use SkyFi\Hotspot\Controllers\VoucherController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $userController = $container->get(HotspotUserController::class);
    $profileController = $container->get(HotspotProfileController::class);
    $voucherController = $container->get(VoucherController::class);
    $sessionController = $container->get(HotspotSessionController::class);
    $syncController = $container->get(HotspotSyncController::class);

    $auth = $container->get(JwtAuthMiddleware::class);

    // Hotspot User Management
    $router->add('GET', '/api/v1/hotspot/users', ProtectRoute::wrap($auth, $userController->index(...)));
    $router->add('POST', '/api/v1/hotspot/users', ProtectRoute::wrap($auth, $userController->store(...)));
    $router->add('POST', '/api/v1/hotspot/users/bulk-import', ProtectRoute::wrap($auth, $userController->bulkImport(...)));
    $router->add('GET', '/api/v1/hotspot/users/{id}', ProtectRoute::wrap($auth, $userController->show(...)));
    $router->add('PUT', '/api/v1/hotspot/users/{id}', ProtectRoute::wrap($auth, $userController->update(...)));
    $router->add('DELETE', '/api/v1/hotspot/users/{id}', ProtectRoute::wrap($auth, $userController->destroy(...)));

    $router->add('PATCH', '/api/v1/hotspot/users/{id}/enable', ProtectRoute::wrap($auth, $userController->enable(...)));
    $router->add('PATCH', '/api/v1/hotspot/users/{id}/disable', ProtectRoute::wrap($auth, $userController->disable(...)));
    $router->add('POST', '/api/v1/hotspot/users/{id}/suspend', ProtectRoute::wrap($auth, $userController->suspend(...)));
    $router->add('POST', '/api/v1/hotspot/users/{id}/resume', ProtectRoute::wrap($auth, $userController->resume(...)));
    $router->add('POST', '/api/v1/hotspot/users/{id}/reset-password', ProtectRoute::wrap($auth, $userController->resetPassword(...)));
    $router->add('PUT', '/api/v1/hotspot/users/{id}/profile', ProtectRoute::wrap($auth, $userController->assignProfile(...)));
    $router->add('PUT', '/api/v1/hotspot/users/{id}/router', ProtectRoute::wrap($auth, $userController->assignRouter(...)));

    // Hotspot Profile Management
    $router->add('GET', '/api/v1/hotspot/profiles', ProtectRoute::wrap($auth, $profileController->index(...)));
    $router->add('POST', '/api/v1/hotspot/profiles', ProtectRoute::wrap($auth, $profileController->store(...)));
    $router->add('GET', '/api/v1/hotspot/profiles/{id}', ProtectRoute::wrap($auth, $profileController->show(...)));
    $router->add('PUT', '/api/v1/hotspot/profiles/{id}', ProtectRoute::wrap($auth, $profileController->update(...)));
    $router->add('DELETE', '/api/v1/hotspot/profiles/{id}', ProtectRoute::wrap($auth, $profileController->destroy(...)));

    // Voucher Management
    $router->add('GET', '/api/v1/hotspot/vouchers/stats', ProtectRoute::wrap($auth, $voucherController->stats(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers/batches', ProtectRoute::wrap($auth, $voucherController->batches(...)));
    $router->add('POST', '/api/v1/hotspot/vouchers/generate', ProtectRoute::wrap($auth, $voucherController->generate(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers', ProtectRoute::wrap($auth, $voucherController->index(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers/{id}', ProtectRoute::wrap($auth, $voucherController->show(...)));
    $router->add('POST', '/api/v1/hotspot/vouchers/{id}/revoke', ProtectRoute::wrap($auth, $voucherController->revoke(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers/batch/{batchId}/print', ProtectRoute::wrap($auth, $voucherController->printBatch(...)));

    // Active Session Management & Monitoring
    $router->add('GET', '/api/v1/hotspot/sessions/active', ProtectRoute::wrap($auth, $sessionController->activeSessions(...)));
    $router->add('POST', '/api/v1/hotspot/sessions/active/disconnect', ProtectRoute::wrap($auth, $sessionController->disconnectSession(...)));
    $router->add('POST', '/api/v1/hotspot/sessions/force-logout', ProtectRoute::wrap($auth, $sessionController->forceLogout(...)));
    $router->add('GET', '/api/v1/hotspot/sessions/history', ProtectRoute::wrap($auth, $sessionController->sessionHistory(...)));
    $router->add('GET', '/api/v1/hotspot/sessions/login-history', ProtectRoute::wrap($auth, $sessionController->loginHistory(...)));
    $router->add('GET', '/api/v1/hotspot/users/{id}/sessions/history', ProtectRoute::wrap($auth, $sessionController->userSessionHistory(...)));
    $router->add('GET', '/api/v1/hotspot/users/{id}/statistics', ProtectRoute::wrap($auth, $sessionController->statistics(...)));

    // Synchronization & Router Interaction
    $router->add('POST', '/api/v1/hotspot/sync/router/{routerId}', ProtectRoute::wrap($auth, $syncController->syncRouter(...)));
    $router->add('POST', '/api/v1/hotspot/sync/user/{id}', ProtectRoute::wrap($auth, $syncController->syncUser(...)));
    $router->add('POST', '/api/v1/hotspot/sync/detect-missing', ProtectRoute::wrap($auth, $syncController->detectMissing(...)));
    $router->add('POST', '/api/v1/hotspot/sync/repair', ProtectRoute::wrap($auth, $syncController->repair(...)));
    $router->add('POST', '/api/v1/hotspot/sync/import', ProtectRoute::wrap($auth, $syncController->importUsers(...)));
    $router->add('POST', '/api/v1/hotspot/sync/import-profiles', ProtectRoute::wrap($auth, $syncController->importProfiles(...)));
    $router->add('GET', '/api/v1/hotspot/routers/{routerId}/profiles', ProtectRoute::wrap($auth, $syncController->routerProfiles(...)));
    $router->add('GET', '/api/v1/hotspot/sync/logs', ProtectRoute::wrap($auth, $syncController->syncLogs(...)));
};
