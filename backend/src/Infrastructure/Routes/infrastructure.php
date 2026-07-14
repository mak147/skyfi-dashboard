<?php

declare(strict_types=1);

use SkyFi\Infrastructure\Controllers\PopSiteController;
use SkyFi\Infrastructure\Controllers\TowerController;
use SkyFi\Infrastructure\Controllers\SectorController;
use SkyFi\Infrastructure\Controllers\NetworkDeviceController;
use SkyFi\Infrastructure\Controllers\InfrastructureDashboardController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $popSiteController = $container->get(PopSiteController::class);
    $towerController = $container->get(TowerController::class);
    $sectorController = $container->get(SectorController::class);
    $deviceController = $container->get(NetworkDeviceController::class);
    $dashboardController = $container->get(InfrastructureDashboardController::class);
    $authMiddleware = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($authMiddleware): callable {
        return static function (Request $request) use ($handler, $authMiddleware) {
            $claims = $authMiddleware->authenticate($request);
            $attributes = $request->attributes();
            $attributes['claims'] = $claims;

            return $handler($request->withAttributes($attributes));
        };
    };

    // Dashboard
    $router->add('GET', '/api/v1/infrastructure/dashboard', $protect($dashboardController->summary(...)));

    // POP Sites
    $router->add('GET', '/api/v1/infrastructure/pop-sites', $protect($popSiteController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/pop-sites', $protect($popSiteController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/map-points', $protect($popSiteController->mapPoints(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}', $protect($popSiteController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/pop-sites/{id}', $protect($popSiteController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/pop-sites/{id}', $protect($popSiteController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/pop-sites/{id}/status', $protect($popSiteController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}/towers', $protect($popSiteController->towers(...)));

    // Towers
    $router->add('GET', '/api/v1/infrastructure/towers', $protect($towerController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/towers', $protect($towerController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/map-points', $protect($towerController->mapPoints(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}', $protect($towerController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/towers/{id}', $protect($towerController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/towers/{id}', $protect($towerController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/towers/{id}/status', $protect($towerController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/sectors', $protect($towerController->sectors(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/devices', $protect($towerController->devices(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}/towers', $protect($towerController->byPopSite(...)));

    // Sectors
    $router->add('GET', '/api/v1/infrastructure/sectors', $protect($sectorController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/sectors', $protect($sectorController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/sectors/coverage', $protect($sectorController->coverage(...)));
    $router->add('GET', '/api/v1/infrastructure/sectors/{id}', $protect($sectorController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/sectors/{id}', $protect($sectorController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/sectors/{id}', $protect($sectorController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/sectors/{id}/status', $protect($sectorController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/sectors/{id}/connections', $protect($sectorController->connections(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/sectors', $protect($sectorController->byTower(...)));

    // Network Devices
    $router->add('GET', '/api/v1/infrastructure/devices', $protect($deviceController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/devices', $protect($deviceController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/devices/by-type/{type}', $protect($deviceController->byType(...)));
    $router->add('GET', '/api/v1/infrastructure/devices/{id}', $protect($deviceController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/devices/{id}', $protect($deviceController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/devices/{id}', $protect($deviceController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/devices/{id}/status', $protect($deviceController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}/devices', $protect($deviceController->byPopSite(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/devices', $protect($deviceController->byTower(...)));
};
