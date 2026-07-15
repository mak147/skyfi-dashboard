<?php

declare(strict_types=1);

use SkyFi\Backup\Controllers\BackupController;
use SkyFi\Backup\Controllers\ScheduleController;
use SkyFi\Backup\Controllers\RestoreController;
use SkyFi\Backup\Controllers\StorageProviderController;
use SkyFi\Backup\Controllers\DrPlanController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
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

    $protect = static fn(callable $h): callable => static function (Request $r) use ($h, $auth) {
        $a = $r->attributes();
        $a['claims'] = $auth->authenticate($r);
        return $h($r->withAttributes($a));
    };

    // Backup Jobs & Files
    $router->add('GET', '/api/v1/backup/jobs', $protect($backup->index(...)));
    $router->add('GET', '/api/v1/backup/statistics', $protect($backup->statistics(...)));
    $router->add('POST', '/api/v1/backup/jobs/manual', $protect($backup->runManual(...)));
    $router->add('GET', '/api/v1/backup/files', $protect($backup->files(...)));
    $router->add('POST', '/api/v1/backup/files/{id}/verify', $protect($backup->verifyFile(...)));
    $router->add('GET', '/api/v1/backup/files/{id}/verification-history', $protect($backup->verificationHistory(...)));

    // Schedules
    $router->add('GET', '/api/v1/backup/schedules', $protect($schedule->index(...)));
    $router->add('POST', '/api/v1/backup/schedules', $protect($schedule->store(...)));
    $router->add('PUT', '/api/v1/backup/schedules/{id}', $protect($schedule->update(...)));
    $router->add('DELETE', '/api/v1/backup/schedules/{id}', $protect($schedule->destroy(...)));

    // Restore
    $router->add('GET', '/api/v1/backup/restore/history', $protect($restore->history(...)));
    $router->add('POST', '/api/v1/backup/restore/execute', $protect($restore->execute(...)));

    // Storage Providers
    $router->add('GET', '/api/v1/backup/storage-providers', $protect($storage->index(...)));
    $router->add('POST', '/api/v1/backup/storage-providers', $protect($storage->store(...)));
    $router->add('PUT', '/api/v1/backup/storage-providers/{id}', $protect($storage->update(...)));

    // Disaster Recovery
    $router->add('GET', '/api/v1/backup/dr-plans', $protect($dr->index(...)));
    $router->add('GET', '/api/v1/backup/dr-plans/{id}', $protect($dr->show(...)));
    $router->add('PUT', '/api/v1/backup/dr-plans/{id}', $protect($dr->update(...)));
};
