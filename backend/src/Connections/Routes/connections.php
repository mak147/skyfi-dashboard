<?php

declare(strict_types=1);

namespace SkyFi\Connections\Routes;

use SkyFi\Connections\Controllers\ConnectionController;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;

return static function (Router $router, Container $container): void {
    $controller = $container->get(ConnectionController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $router->add('GET', '/api/v1/connections', fn($req) => $auth->handle($req, fn($r) => $controller->index($r)));
    $router->add('POST', '/api/v1/connections', fn($req) => $auth->handle($req, fn($r) => $controller->store($r)));
    $router->add('GET', '/api/v1/connections/{id}', fn($req) => $auth->handle($req, fn($r) => $controller->show($r)));
    $router->add('PUT', '/api/v1/connections/{id}', fn($req) => $auth->handle($req, fn($r) => $controller->update($r)));
    $router->add('DELETE', '/api/v1/connections/{id}', fn($req) => $auth->handle($req, fn($r) => $controller->destroy($r)));
    
    $router->add('PATCH', '/api/v1/connections/{id}/activate', fn($req) => $auth->handle($req, fn($r) => $controller->activate($r)));
    $router->add('PATCH', '/api/v1/connections/{id}/suspend', fn($req) => $auth->handle($req, fn($r) => $controller->suspend($r)));
    $router->add('PATCH', '/api/v1/connections/{id}/disconnect', fn($req) => $auth->handle($req, fn($r) => $controller->disconnect($r)));
    $router->add('PATCH', '/api/v1/connections/{id}/transfer', fn($req) => $auth->handle($req, fn($r) => $controller->transfer($r)));
};
