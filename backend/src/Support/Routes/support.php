<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;
use SkyFi\Support\Controllers\{
    SupportConfigurationController,
    SupportDashboardController,
    SupportLookupController,
    TicketActionController,
    TicketCommentController,
    TicketController,
    TicketTimelineController,
};

return static function (Router $router, Container $container): void {
    $tickets = $container->get(TicketController::class);
    $actions = $container->get(TicketActionController::class);
    $comments = $container->get(TicketCommentController::class);
    $timeline = $container->get(TicketTimelineController::class);
    $dashboard = $container->get(SupportDashboardController::class);
    $lookups = $container->get(SupportLookupController::class);
    $config = $container->get(SupportConfigurationController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $router->add(
        "GET",
        "/api/v1/support/dashboard",
        ProtectRoute::wrap($auth, $dashboard->dashboard(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/sla/dashboard",
        ProtectRoute::wrap($auth, $dashboard->sla(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/sla/process",
        ProtectRoute::wrap($auth, $dashboard->process(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/lookups/{resource}",
        ProtectRoute::wrap($auth, $lookups->lookup(...)),
    );
    $withResource = static fn(
        string $resource,
        callable $handler,
    ): callable => static function (Request $request) use (
        $resource,
        $handler,
    ) {
        $attributes = $request->attributes();
        $attributes["route_params"] = [
            ...$attributes["route_params"] ?? [],
            "resource" => $resource,
        ];
        return $handler($request->withAttributes($attributes));
    };
    foreach (["categories", "teams", "sla-policies"] as $resource) {
        $router->add(
            "GET",
            "/api/v1/support/" . $resource,
            ProtectRoute::wrap($auth, $withResource($resource, $config->index(...))),
        );
        $router->add(
            "POST",
            "/api/v1/support/" . $resource,
            ProtectRoute::wrap($auth, $withResource($resource, $config->store(...))),
        );
        $router->add(
            "PUT",
            "/api/v1/support/" . $resource . "/{configId}",
            ProtectRoute::wrap($auth, $withResource($resource, $config->update(...))),
        );
        $router->add(
            "DELETE",
            "/api/v1/support/" . $resource . "/{configId}",
            ProtectRoute::wrap($auth, $withResource($resource, $config->destroy(...))),
        );
    }
    $router->add(
        "GET",
        "/api/v1/support/config/{resource}",
        ProtectRoute::wrap($auth, $config->index(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/config/{resource}",
        ProtectRoute::wrap($auth, $config->store(...)),
    );
    $router->add(
        "PUT",
        "/api/v1/support/config/{resource}/{configId}",
        ProtectRoute::wrap($auth, $config->update(...)),
    );
    $router->add(
        "DELETE",
        "/api/v1/support/config/{resource}/{configId}",
        ProtectRoute::wrap($auth, $config->destroy(...)),
    );

    $router->add(
        "GET",
        "/api/v1/support/tickets",
        ProtectRoute::wrap($auth, $tickets->index(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets",
        ProtectRoute::wrap($auth, $tickets->store(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}",
        ProtectRoute::wrap($auth, $tickets->show(...)),
    );
    $router->add(
        "PUT",
        "/api/v1/support/tickets/{id}",
        ProtectRoute::wrap($auth, $tickets->update(...)),
    );
    $router->add(
        "DELETE",
        "/api/v1/support/tickets/{id}",
        ProtectRoute::wrap($auth, $tickets->destroy(...)),
    );
    $router->add(
        "PATCH",
        "/api/v1/support/tickets/{id}/status",
        ProtectRoute::wrap($auth, $actions->status(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/assign",
        ProtectRoute::wrap($auth, $actions->assign(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/reassign",
        ProtectRoute::wrap($auth, $actions->assign(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/resolve",
        ProtectRoute::wrap($auth, $actions->resolve(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/close",
        ProtectRoute::wrap($auth, $actions->close(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/reopen",
        ProtectRoute::wrap($auth, $actions->reopen(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/cancel",
        ProtectRoute::wrap($auth, $actions->cancel(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/escalate",
        ProtectRoute::wrap($auth, $actions->escalate(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/merge",
        ProtectRoute::wrap($auth, $actions->merge(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/split",
        ProtectRoute::wrap($auth, $actions->split(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}/comments",
        ProtectRoute::wrap($auth, $comments->index(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/comments",
        ProtectRoute::wrap($auth, $comments->store(...)),
    );
    $router->add(
        "PUT",
        "/api/v1/support/tickets/{id}/comments/{commentId}",
        ProtectRoute::wrap($auth, $comments->update(...)),
    );
    $router->add(
        "DELETE",
        "/api/v1/support/tickets/{id}/comments/{commentId}",
        ProtectRoute::wrap($auth, $comments->destroy(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}/timeline",
        ProtectRoute::wrap($auth, $timeline->timeline(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}/assignments",
        ProtectRoute::wrap($auth, $timeline->assignments(...)),
    );
};
