<?php

declare(strict_types=1);

use SkyFi\Monitoring\Controllers\AlertController;
use SkyFi\Monitoring\Controllers\DeviceHealthController;
use SkyFi\Monitoring\Controllers\EventLogController;
use SkyFi\Monitoring\Controllers\MonitoringDashboardController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $dashboardController = $container->get(MonitoringDashboardController::class);
    $healthController = $container->get(DeviceHealthController::class);
    $alertController = $container->get(AlertController::class);
    $eventLogController = $container->get(EventLogController::class);

    $auth = $container->get(JwtAuthMiddleware::class);

    // Dashboard & Metrics
    $router->add('GET', '/api/v1/monitoring/dashboard', ProtectRoute::wrap($auth, $dashboardController->overview(...)));
    $router->add('GET', '/api/v1/monitoring/routers/{id}/metrics', ProtectRoute::wrap($auth, $dashboardController->routerDetailedMetrics(...)));

    // Device Health & Interfaces Polling
    $router->add('GET', '/api/v1/monitoring/devices/health', ProtectRoute::wrap($auth, $healthController->listDeviceHealth(...)));
    $router->add('POST', '/api/v1/monitoring/routers/{id}/poll', ProtectRoute::wrap($auth, $healthController->pollRouter(...)));
    $router->add('POST', '/api/v1/monitoring/poll-all', ProtectRoute::wrap($auth, $healthController->pollAll(...)));
    $router->add('GET', '/api/v1/monitoring/interfaces', ProtectRoute::wrap($auth, $healthController->listInterfaces(...)));

    // Alerts Management
    $router->add('GET', '/api/v1/monitoring/alerts', ProtectRoute::wrap($auth, $alertController->index(...)));
    $router->add('POST', '/api/v1/monitoring/alerts', ProtectRoute::wrap($auth, $alertController->store(...)));
    $router->add('GET', '/api/v1/monitoring/alerts/{id}', ProtectRoute::wrap($auth, $alertController->show(...)));
    $router->add('POST', '/api/v1/monitoring/alerts/{id}/acknowledge', ProtectRoute::wrap($auth, $alertController->acknowledge(...)));
    $router->add('POST', '/api/v1/monitoring/alerts/{id}/resolve', ProtectRoute::wrap($auth, $alertController->resolve(...)));
    $router->add('POST', '/api/v1/monitoring/alerts/{id}/dismiss', ProtectRoute::wrap($auth, $alertController->dismiss(...)));

    // Event & Sync Logs
    $router->add('GET', '/api/v1/monitoring/events', ProtectRoute::wrap($auth, $eventLogController->listMonitoringEvents(...)));
    $router->add('GET', '/api/v1/monitoring/sync-history', ProtectRoute::wrap($auth, $eventLogController->listSyncEvents(...)));
};
