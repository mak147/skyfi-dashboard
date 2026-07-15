<?php

declare(strict_types=1);

use SkyFi\Billing\Controllers\InvoiceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $c = $container->get(InvoiceController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $router->add('GET', '/api/v1/invoices', ProtectRoute::wrap($auth, $c->index(...)));
    $router->add('POST', '/api/v1/invoices', ProtectRoute::wrap($auth, $c->store(...)));
    $router->add('GET', '/api/v1/invoices/statistics', ProtectRoute::wrap($auth, $c->statistics(...)));
    $router->add('GET', '/api/v1/invoices/{id}', ProtectRoute::wrap($auth, $c->show(...)));
    $router->add('PUT', '/api/v1/invoices/{id}', ProtectRoute::wrap($auth, $c->update(...)));
    $router->add('DELETE', '/api/v1/invoices/{id}', ProtectRoute::wrap($auth, $c->destroy(...)));
    $router->add('PATCH', '/api/v1/invoices/{id}/status', ProtectRoute::wrap($auth, $c->changeStatus(...)));
    $router->add('POST', '/api/v1/invoices/generate', ProtectRoute::wrap($auth, $c->generate(...)));
    $router->add('POST', '/api/v1/invoices/bulk-generate', ProtectRoute::wrap($auth, $c->bulkGenerate(...)));
    $router->add('GET', '/api/v1/invoices/{id}/activity', ProtectRoute::wrap($auth, $c->activity(...)));
};
