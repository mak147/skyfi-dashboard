<?php

declare(strict_types=1);

use SkyFi\Billing\Controllers\InvoiceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $c = $container->get(InvoiceController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static fn(callable $h): callable => static function (Request $r) use ($h, $auth) {
        $a = $r->attributes();
        $a['claims'] = $auth->authenticate($r);
        return $h($r->withAttributes($a));
    };

    $router->add('GET', '/api/v1/invoices', $protect($c->index(...)));
    $router->add('POST', '/api/v1/invoices', $protect($c->store(...)));
    $router->add('GET', '/api/v1/invoices/statistics', $protect($c->statistics(...)));
    $router->add('GET', '/api/v1/invoices/{id}', $protect($c->show(...)));
    $router->add('PUT', '/api/v1/invoices/{id}', $protect($c->update(...)));
    $router->add('DELETE', '/api/v1/invoices/{id}', $protect($c->destroy(...)));
    $router->add('PATCH', '/api/v1/invoices/{id}/status', $protect($c->changeStatus(...)));
    $router->add('POST', '/api/v1/invoices/generate', $protect($c->generate(...)));
    $router->add('POST', '/api/v1/invoices/bulk-generate', $protect($c->bulkGenerate(...)));
    $router->add('GET', '/api/v1/invoices/{id}/activity', $protect($c->activity(...)));
};
