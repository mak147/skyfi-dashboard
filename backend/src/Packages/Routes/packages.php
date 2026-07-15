<?php

declare(strict_types=1);
use SkyFi\Packages\Controllers\PackageController;
use SkyFi\Shared\Http\{Request, Router};
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;
return static function (Router $router, Container $container): void {
    $c = $container->get(PackageController::class);
    $auth = $container->get(JwtAuthMiddleware::class);
    $router->add("GET", "/api/v1/packages", ProtectRoute::wrap($auth, $c->index(...)));
    $router->add("POST", "/api/v1/packages", ProtectRoute::wrap($auth, $c->store(...)));
    $router->add(
        "GET",
        "/api/v1/packages/statistics",
        ProtectRoute::wrap($auth, $c->statistics(...)),
    );
    $router->add("GET", "/api/v1/packages/export", ProtectRoute::wrap($auth, $c->export(...)));
    $router->add(
        "PATCH",
        "/api/v1/packages/bulk/status",
        ProtectRoute::wrap($auth, $c->bulkStatus(...)),
    );
    $router->add(
        "DELETE",
        "/api/v1/packages/bulk",
        ProtectRoute::wrap($auth, $c->bulkDelete(...)),
    );
    $router->add("GET", "/api/v1/packages/{id}", ProtectRoute::wrap($auth, $c->show(...)));
    $router->add("PUT", "/api/v1/packages/{id}", ProtectRoute::wrap($auth, $c->update(...)));
    $router->add("DELETE", "/api/v1/packages/{id}", ProtectRoute::wrap($auth, $c->destroy(...)));
    $router->add(
        "PATCH",
        "/api/v1/packages/{id}/status",
        ProtectRoute::wrap($auth, $c->status(...)),
    );
    $router->add(
        "POST",
        "/api/v1/packages/{id}/duplicate",
        ProtectRoute::wrap($auth, $c->duplicate(...)),
    );
    $router->add(
        "GET",
        "/api/v1/packages/{id}/activity",
        ProtectRoute::wrap($auth, $c->activity(...)),
    );
};
