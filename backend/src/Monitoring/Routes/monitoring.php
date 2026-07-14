<?php

declare(strict_types=1);

use SkyFi\Monitoring\Controllers\AlertController;
use SkyFi\Monitoring\Controllers\DeviceHealthController;
use SkyFi\Monitoring\Controllers\EventLogController;
use SkyFi\Monitoring\Controllers\MonitoringDashboardController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $dashboardController = $container->get(MonitoringDashboardController::class);
    $healthController = $container->get(DeviceHealthController::class);
    $alertController = $container->get(AlertController::class);
    $eventLogController = $container->get(EventLogController::class);

    $auth = $container->get(JwtAuthMiddleware::class);
    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // Dashboard & Metrics
    $router->add('GET', '/api/v1/monitoring/dashboard', $protect($dashboardController->overview(...)));
    $router->add('GET', '/api/v1/monitoring/routers/{id}/metrics', $protect($dashboardController->routerDetailedMetrics(...)));

    // Device Health & Interfaces Polling
    $router->add('GET', '/api/v1/monitoring/devices/health', $protect($healthController->listDeviceHealth(...)));
    $router->add('POST', '/api/v1/monitoring/routers/{id}/poll', $protect($healthController->pollRouter(...)));
    $router->add('POST', '/api/v1/monitoring/poll-all', $protect($healthController->pollAll(...)));
    $router->add('GET', '/api/v1/monitoring/interfaces', $protect($healthController->listInterfaces(...)));

    // Alerts Management
    $router->add('GET', '/api/v1/monitoring/alerts', $protect($alertController->index(...)));
    $router->add('POST', '/api/v1/monitoring/alerts', $protect($alertController->store(...)));
    $router->add('GET', '/api/v1/monitoring/alerts/{id}', $protect($alertController->show(...)));
    $router->add('POST', '/api/v1/monitoring/alerts/{id}/acknowledge', $protect($alertController->acknowledge(...)));
    $router->add('POST', '/api/v1/monitoring/alerts/{id}/resolve', $protect($alertController->resolve(...)));
    $router->add('POST', '/api/v1/monitoring/alerts/{id}/dismiss', $protect($alertController->dismiss(...)));

    // Event & Sync Logs
    $router->add('GET', '/api/v1/monitoring/events', $protect($eventLogController->listMonitoringEvents(...)));
    $router->add('GET', '/api/v1/monitoring/sync-history', $protect($eventLogController->listSyncEvents(...)));
};
