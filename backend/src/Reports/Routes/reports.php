<?php

declare(strict_types=1);

use SkyFi\Reports\Controllers\{ReportConfigurationController,ReportController,ReportExportController};
use SkyFi\Shared\Http\{Request,Router};
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Providers\Container;

return static function(Router$router,Container$container):void{
    $reports=$container->get(ReportController::class);$config=$container->get(ReportConfigurationController::class);$exports=$container->get(ReportExportController::class);$auth=$container->get(JwtAuthMiddleware::class);
    $router->add('GET','/api/v1/reports/catalog',ProtectRoute::wrap($auth, $reports->catalog(...)));
    $router->add('GET','/api/v1/reports/filters',ProtectRoute::wrap($auth, $reports->filters(...)));
    $router->add('POST','/api/v1/reports/generate',ProtectRoute::wrap($auth, $reports->generate(...)));
    $router->add('GET','/api/v1/reports/dashboards/{dashboard}',ProtectRoute::wrap($auth, $reports->dashboard(...)));
    $router->add('GET','/api/v1/reports/saved',ProtectRoute::wrap($auth, $config->saved(...)));$router->add('POST','/api/v1/reports/saved',ProtectRoute::wrap($auth, $config->savedStore(...)));$router->add('GET','/api/v1/reports/saved/{id}',ProtectRoute::wrap($auth, $config->savedShow(...)));$router->add('PUT','/api/v1/reports/saved/{id}',ProtectRoute::wrap($auth, $config->savedUpdate(...)));$router->add('DELETE','/api/v1/reports/saved/{id}',ProtectRoute::wrap($auth, $config->savedDelete(...)));$router->add('POST','/api/v1/reports/saved/{id}/run',ProtectRoute::wrap($auth, $config->savedRun(...)));
    $router->add('GET','/api/v1/reports/templates',ProtectRoute::wrap($auth, $config->templates(...)));$router->add('POST','/api/v1/reports/templates',ProtectRoute::wrap($auth, $config->templateStore(...)));$router->add('GET','/api/v1/reports/templates/{id}',ProtectRoute::wrap($auth, $config->templateShow(...)));$router->add('PUT','/api/v1/reports/templates/{id}',ProtectRoute::wrap($auth, $config->templateUpdate(...)));$router->add('DELETE','/api/v1/reports/templates/{id}',ProtectRoute::wrap($auth, $config->templateDelete(...)));
    $router->add('GET','/api/v1/reports/schedules',ProtectRoute::wrap($auth, $config->schedules(...)));$router->add('POST','/api/v1/reports/schedules',ProtectRoute::wrap($auth, $config->scheduleStore(...)));$router->add('GET','/api/v1/reports/schedules/{id}',ProtectRoute::wrap($auth, $config->scheduleShow(...)));$router->add('PUT','/api/v1/reports/schedules/{id}',ProtectRoute::wrap($auth, $config->scheduleUpdate(...)));$router->add('DELETE','/api/v1/reports/schedules/{id}',ProtectRoute::wrap($auth, $config->scheduleDelete(...)));
    $router->add('GET','/api/v1/reports/exports',ProtectRoute::wrap($auth, $exports->index(...)));$router->add('POST','/api/v1/reports/exports',ProtectRoute::wrap($auth, $exports->store(...)));$router->add('GET','/api/v1/reports/exports/{id}/download',ProtectRoute::wrap($auth, $exports->download(...)));$router->add('GET','/api/v1/reports/exports/{id}',ProtectRoute::wrap($auth, $exports->show(...)));$router->add('DELETE','/api/v1/reports/exports/{id}',ProtectRoute::wrap($auth, $exports->destroy(...)));
};
