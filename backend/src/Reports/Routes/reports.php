<?php

declare(strict_types=1);

use SkyFi\Reports\Controllers\{ReportConfigurationController,ReportController,ReportExportController};
use SkyFi\Shared\Http\{Request,Router};
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Providers\Container;

return static function(Router$router,Container$container):void{
    $reports=$container->get(ReportController::class);$config=$container->get(ReportConfigurationController::class);$exports=$container->get(ReportExportController::class);$auth=$container->get(JwtAuthMiddleware::class);
    $protect=static fn(callable$handler):callable=>static function(Request$request)use($auth,$handler){$attributes=$request->attributes();$attributes['claims']=$auth->authenticate($request);return$handler($request->withAttributes($attributes));};
    $router->add('GET','/api/v1/reports/catalog',$protect($reports->catalog(...)));
    $router->add('GET','/api/v1/reports/filters',$protect($reports->filters(...)));
    $router->add('POST','/api/v1/reports/generate',$protect($reports->generate(...)));
    $router->add('GET','/api/v1/reports/dashboards/{dashboard}',$protect($reports->dashboard(...)));
    $router->add('GET','/api/v1/reports/saved',$protect($config->saved(...)));$router->add('POST','/api/v1/reports/saved',$protect($config->savedStore(...)));$router->add('GET','/api/v1/reports/saved/{id}',$protect($config->savedShow(...)));$router->add('PUT','/api/v1/reports/saved/{id}',$protect($config->savedUpdate(...)));$router->add('DELETE','/api/v1/reports/saved/{id}',$protect($config->savedDelete(...)));$router->add('POST','/api/v1/reports/saved/{id}/run',$protect($config->savedRun(...)));
    $router->add('GET','/api/v1/reports/templates',$protect($config->templates(...)));$router->add('POST','/api/v1/reports/templates',$protect($config->templateStore(...)));$router->add('GET','/api/v1/reports/templates/{id}',$protect($config->templateShow(...)));$router->add('PUT','/api/v1/reports/templates/{id}',$protect($config->templateUpdate(...)));$router->add('DELETE','/api/v1/reports/templates/{id}',$protect($config->templateDelete(...)));
    $router->add('GET','/api/v1/reports/schedules',$protect($config->schedules(...)));$router->add('POST','/api/v1/reports/schedules',$protect($config->scheduleStore(...)));$router->add('GET','/api/v1/reports/schedules/{id}',$protect($config->scheduleShow(...)));$router->add('PUT','/api/v1/reports/schedules/{id}',$protect($config->scheduleUpdate(...)));$router->add('DELETE','/api/v1/reports/schedules/{id}',$protect($config->scheduleDelete(...)));
    $router->add('GET','/api/v1/reports/exports',$protect($exports->index(...)));$router->add('POST','/api/v1/reports/exports',$protect($exports->store(...)));$router->add('GET','/api/v1/reports/exports/{id}/download',$protect($exports->download(...)));$router->add('GET','/api/v1/reports/exports/{id}',$protect($exports->show(...)));$router->add('DELETE','/api/v1/reports/exports/{id}',$protect($exports->destroy(...)));
};
