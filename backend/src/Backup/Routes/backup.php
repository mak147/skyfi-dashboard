<?php

declare(strict_types=1);

use SkyFi\Backup\Controllers\BackupController;
use SkyFi\Backup\Controllers\ScheduleController;
use SkyFi\Backup\Controllers\RestoreController;
use SkyFi\Backup\Controllers\StorageProviderController;
use SkyFi\Backup\Controllers\DrPlanController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $backup = $container->get(BackupController::class);
    $schedule = $container->get(ScheduleController::class);
    $restore = $container->get(RestoreController::class);
    $storage = $container->get(StorageProviderController::class);
    $dr = $container->get(DrPlanController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    // Backup Jobs & Files
    $router->add('GET', '/api/v1/backup/jobs', ProtectRoute::wrap($auth, $backup->index(...)));
    $router->add('GET', '/api/v1/backup/statistics', ProtectRoute::wrap($auth, $backup->statistics(...)));
    $router->add('POST', '/api/v1/backup/jobs/manual', ProtectRoute::wrap($auth, $backup->runManual(...)));
    $router->add('GET', '/api/v1/backup/files', ProtectRoute::wrap($auth, $backup->files(...)));
    $router->add('POST', '/api/v1/backup/files/{id}/verify', ProtectRoute::wrap($auth, $backup->verifyFile(...)));
    $router->add('GET', '/api/v1/backup/files/{id}/verification-history', ProtectRoute::wrap($auth, $backup->verificationHistory(...)));

    // Schedules
    $router->add('GET', '/api/v1/backup/schedules', ProtectRoute::wrap($auth, $schedule->index(...)));
    $router->add('POST', '/api/v1/backup/schedules', ProtectRoute::wrap($auth, $schedule->store(...)));
    $router->add('PUT', '/api/v1/backup/schedules/{id}', ProtectRoute::wrap($auth, $schedule->update(...)));
    $router->add('DELETE', '/api/v1/backup/schedules/{id}', ProtectRoute::wrap($auth, $schedule->destroy(...)));

    // Restore
    $router->add('GET', '/api/v1/backup/restore/history', ProtectRoute::wrap($auth, $restore->history(...)));
    $router->add('POST', '/api/v1/backup/restore/execute', ProtectRoute::wrap($auth, $restore->execute(...)));

    // Storage Providers
    $router->add('GET', '/api/v1/backup/storage-providers', ProtectRoute::wrap($auth, $storage->index(...)));
    $router->add('POST', '/api/v1/backup/storage-providers', ProtectRoute::wrap($auth, $storage->store(...)));
    $router->add('PUT', '/api/v1/backup/storage-providers/{id}', ProtectRoute::wrap($auth, $storage->update(...)));

    // Disaster Recovery
    $router->add('GET', '/api/v1/backup/dr-plans', ProtectRoute::wrap($auth, $dr->index(...)));
    $router->add('GET', '/api/v1/backup/dr-plans/{id}', ProtectRoute::wrap($auth, $dr->show(...)));
    $router->add('PUT', '/api/v1/backup/dr-plans/{id}', ProtectRoute::wrap($auth, $dr->update(...)));
};
