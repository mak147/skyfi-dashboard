<?php

declare(strict_types=1);

use SkyFi\Infrastructure\Controllers\PopSiteController;
use SkyFi\Infrastructure\Controllers\TowerController;
use SkyFi\Infrastructure\Controllers\SectorController;
use SkyFi\Infrastructure\Controllers\NetworkDeviceController;
use SkyFi\Infrastructure\Controllers\InfrastructureDashboardController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
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

    // Dashboard
    $router->add('GET', '/api/v1/infrastructure/dashboard', ProtectRoute::wrap($authMiddleware, $dashboardController->summary(...)));

    // POP Sites
    $router->add('GET', '/api/v1/infrastructure/pop-sites', ProtectRoute::wrap($authMiddleware, $popSiteController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/pop-sites', ProtectRoute::wrap($authMiddleware, $popSiteController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/map-points', ProtectRoute::wrap($authMiddleware, $popSiteController->mapPoints(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}', ProtectRoute::wrap($authMiddleware, $popSiteController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/pop-sites/{id}', ProtectRoute::wrap($authMiddleware, $popSiteController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/pop-sites/{id}', ProtectRoute::wrap($authMiddleware, $popSiteController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/pop-sites/{id}/status', ProtectRoute::wrap($authMiddleware, $popSiteController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}/towers', ProtectRoute::wrap($authMiddleware, $popSiteController->towers(...)));

    // Towers
    $router->add('GET', '/api/v1/infrastructure/towers', ProtectRoute::wrap($authMiddleware, $towerController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/towers', ProtectRoute::wrap($authMiddleware, $towerController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/map-points', ProtectRoute::wrap($authMiddleware, $towerController->mapPoints(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}', ProtectRoute::wrap($authMiddleware, $towerController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/towers/{id}', ProtectRoute::wrap($authMiddleware, $towerController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/towers/{id}', ProtectRoute::wrap($authMiddleware, $towerController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/towers/{id}/status', ProtectRoute::wrap($authMiddleware, $towerController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/sectors', ProtectRoute::wrap($authMiddleware, $towerController->sectors(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/devices', ProtectRoute::wrap($authMiddleware, $towerController->devices(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}/towers', ProtectRoute::wrap($authMiddleware, $towerController->byPopSite(...)));

    // Sectors
    $router->add('GET', '/api/v1/infrastructure/sectors', ProtectRoute::wrap($authMiddleware, $sectorController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/sectors', ProtectRoute::wrap($authMiddleware, $sectorController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/sectors/coverage', ProtectRoute::wrap($authMiddleware, $sectorController->coverage(...)));
    $router->add('GET', '/api/v1/infrastructure/sectors/{id}', ProtectRoute::wrap($authMiddleware, $sectorController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/sectors/{id}', ProtectRoute::wrap($authMiddleware, $sectorController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/sectors/{id}', ProtectRoute::wrap($authMiddleware, $sectorController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/sectors/{id}/status', ProtectRoute::wrap($authMiddleware, $sectorController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/sectors/{id}/connections', ProtectRoute::wrap($authMiddleware, $sectorController->connections(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/sectors', ProtectRoute::wrap($authMiddleware, $sectorController->byTower(...)));

    // Network Devices
    $router->add('GET', '/api/v1/infrastructure/devices', ProtectRoute::wrap($authMiddleware, $deviceController->index(...)));
    $router->add('POST', '/api/v1/infrastructure/devices', ProtectRoute::wrap($authMiddleware, $deviceController->store(...)));
    $router->add('GET', '/api/v1/infrastructure/devices/by-type/{type}', ProtectRoute::wrap($authMiddleware, $deviceController->byType(...)));
    $router->add('GET', '/api/v1/infrastructure/devices/{id}', ProtectRoute::wrap($authMiddleware, $deviceController->show(...)));
    $router->add('PUT', '/api/v1/infrastructure/devices/{id}', ProtectRoute::wrap($authMiddleware, $deviceController->update(...)));
    $router->add('DELETE', '/api/v1/infrastructure/devices/{id}', ProtectRoute::wrap($authMiddleware, $deviceController->destroy(...)));
    $router->add('PATCH', '/api/v1/infrastructure/devices/{id}/status', ProtectRoute::wrap($authMiddleware, $deviceController->changeStatus(...)));
    $router->add('GET', '/api/v1/infrastructure/pop-sites/{id}/devices', ProtectRoute::wrap($authMiddleware, $deviceController->byPopSite(...)));
    $router->add('GET', '/api/v1/infrastructure/towers/{id}/devices', ProtectRoute::wrap($authMiddleware, $deviceController->byTower(...)));
};
