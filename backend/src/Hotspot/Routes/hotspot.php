<?php

declare(strict_types=1);

use SkyFi\Hotspot\Controllers\HotspotProfileController;
use SkyFi\Hotspot\Controllers\HotspotSessionController;
use SkyFi\Hotspot\Controllers\HotspotSyncController;
use SkyFi\Hotspot\Controllers\HotspotUserController;
use SkyFi\Hotspot\Controllers\VoucherController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
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
    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // Hotspot User Management
    $router->add('GET', '/api/v1/hotspot/users', $protect($userController->index(...)));
    $router->add('POST', '/api/v1/hotspot/users', $protect($userController->store(...)));
    $router->add('POST', '/api/v1/hotspot/users/bulk-import', $protect($userController->bulkImport(...)));
    $router->add('GET', '/api/v1/hotspot/users/{id}', $protect($userController->show(...)));
    $router->add('PUT', '/api/v1/hotspot/users/{id}', $protect($userController->update(...)));
    $router->add('DELETE', '/api/v1/hotspot/users/{id}', $protect($userController->destroy(...)));

    $router->add('PATCH', '/api/v1/hotspot/users/{id}/enable', $protect($userController->enable(...)));
    $router->add('PATCH', '/api/v1/hotspot/users/{id}/disable', $protect($userController->disable(...)));
    $router->add('POST', '/api/v1/hotspot/users/{id}/suspend', $protect($userController->suspend(...)));
    $router->add('POST', '/api/v1/hotspot/users/{id}/resume', $protect($userController->resume(...)));
    $router->add('POST', '/api/v1/hotspot/users/{id}/reset-password', $protect($userController->resetPassword(...)));
    $router->add('PUT', '/api/v1/hotspot/users/{id}/profile', $protect($userController->assignProfile(...)));
    $router->add('PUT', '/api/v1/hotspot/users/{id}/router', $protect($userController->assignRouter(...)));

    // Hotspot Profile Management
    $router->add('GET', '/api/v1/hotspot/profiles', $protect($profileController->index(...)));
    $router->add('POST', '/api/v1/hotspot/profiles', $protect($profileController->store(...)));
    $router->add('GET', '/api/v1/hotspot/profiles/{id}', $protect($profileController->show(...)));
    $router->add('PUT', '/api/v1/hotspot/profiles/{id}', $protect($profileController->update(...)));
    $router->add('DELETE', '/api/v1/hotspot/profiles/{id}', $protect($profileController->destroy(...)));

    // Voucher Management
    $router->add('GET', '/api/v1/hotspot/vouchers/stats', $protect($voucherController->stats(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers/batches', $protect($voucherController->batches(...)));
    $router->add('POST', '/api/v1/hotspot/vouchers/generate', $protect($voucherController->generate(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers', $protect($voucherController->index(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers/{id}', $protect($voucherController->show(...)));
    $router->add('POST', '/api/v1/hotspot/vouchers/{id}/revoke', $protect($voucherController->revoke(...)));
    $router->add('GET', '/api/v1/hotspot/vouchers/batch/{batchId}/print', $protect($voucherController->printBatch(...)));

    // Active Session Management & Monitoring
    $router->add('GET', '/api/v1/hotspot/sessions/active', $protect($sessionController->activeSessions(...)));
    $router->add('POST', '/api/v1/hotspot/sessions/active/disconnect', $protect($sessionController->disconnectSession(...)));
    $router->add('POST', '/api/v1/hotspot/sessions/force-logout', $protect($sessionController->forceLogout(...)));
    $router->add('GET', '/api/v1/hotspot/sessions/history', $protect($sessionController->sessionHistory(...)));
    $router->add('GET', '/api/v1/hotspot/sessions/login-history', $protect($sessionController->loginHistory(...)));
    $router->add('GET', '/api/v1/hotspot/users/{id}/sessions/history', $protect($sessionController->userSessionHistory(...)));
    $router->add('GET', '/api/v1/hotspot/users/{id}/statistics', $protect($sessionController->statistics(...)));

    // Synchronization & Router Interaction
    $router->add('POST', '/api/v1/hotspot/sync/router/{routerId}', $protect($syncController->syncRouter(...)));
    $router->add('POST', '/api/v1/hotspot/sync/user/{id}', $protect($syncController->syncUser(...)));
    $router->add('POST', '/api/v1/hotspot/sync/detect-missing', $protect($syncController->detectMissing(...)));
    $router->add('POST', '/api/v1/hotspot/sync/repair', $protect($syncController->repair(...)));
    $router->add('POST', '/api/v1/hotspot/sync/import', $protect($syncController->importUsers(...)));
    $router->add('POST', '/api/v1/hotspot/sync/import-profiles', $protect($syncController->importProfiles(...)));
    $router->add('GET', '/api/v1/hotspot/routers/{routerId}/profiles', $protect($syncController->routerProfiles(...)));
    $router->add('GET', '/api/v1/hotspot/sync/logs', $protect($syncController->syncLogs(...)));
};
