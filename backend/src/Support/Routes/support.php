<?php

declare(strict_types=1);

use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
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
    $protect = static fn(callable $handler): callable => static function (
        Request $request,
    ) use ($auth, $handler) {
        $attributes = $request->attributes();
        $attributes["claims"] = $auth->authenticate($request);
        return $handler($request->withAttributes($attributes));
    };

    $router->add(
        "GET",
        "/api/v1/support/dashboard",
        $protect($dashboard->dashboard(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/sla/dashboard",
        $protect($dashboard->sla(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/sla/process",
        $protect($dashboard->process(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/lookups/{resource}",
        $protect($lookups->lookup(...)),
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
            $protect($withResource($resource, $config->index(...))),
        );
        $router->add(
            "POST",
            "/api/v1/support/" . $resource,
            $protect($withResource($resource, $config->store(...))),
        );
        $router->add(
            "PUT",
            "/api/v1/support/" . $resource . "/{configId}",
            $protect($withResource($resource, $config->update(...))),
        );
        $router->add(
            "DELETE",
            "/api/v1/support/" . $resource . "/{configId}",
            $protect($withResource($resource, $config->destroy(...))),
        );
    }
    $router->add(
        "GET",
        "/api/v1/support/config/{resource}",
        $protect($config->index(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/config/{resource}",
        $protect($config->store(...)),
    );
    $router->add(
        "PUT",
        "/api/v1/support/config/{resource}/{configId}",
        $protect($config->update(...)),
    );
    $router->add(
        "DELETE",
        "/api/v1/support/config/{resource}/{configId}",
        $protect($config->destroy(...)),
    );

    $router->add(
        "GET",
        "/api/v1/support/tickets",
        $protect($tickets->index(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets",
        $protect($tickets->store(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}",
        $protect($tickets->show(...)),
    );
    $router->add(
        "PUT",
        "/api/v1/support/tickets/{id}",
        $protect($tickets->update(...)),
    );
    $router->add(
        "DELETE",
        "/api/v1/support/tickets/{id}",
        $protect($tickets->destroy(...)),
    );
    $router->add(
        "PATCH",
        "/api/v1/support/tickets/{id}/status",
        $protect($actions->status(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/assign",
        $protect($actions->assign(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/reassign",
        $protect($actions->assign(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/resolve",
        $protect($actions->resolve(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/close",
        $protect($actions->close(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/reopen",
        $protect($actions->reopen(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/cancel",
        $protect($actions->cancel(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/escalate",
        $protect($actions->escalate(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/merge",
        $protect($actions->merge(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/split",
        $protect($actions->split(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}/comments",
        $protect($comments->index(...)),
    );
    $router->add(
        "POST",
        "/api/v1/support/tickets/{id}/comments",
        $protect($comments->store(...)),
    );
    $router->add(
        "PUT",
        "/api/v1/support/tickets/{id}/comments/{commentId}",
        $protect($comments->update(...)),
    );
    $router->add(
        "DELETE",
        "/api/v1/support/tickets/{id}/comments/{commentId}",
        $protect($comments->destroy(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}/timeline",
        $protect($timeline->timeline(...)),
    );
    $router->add(
        "GET",
        "/api/v1/support/tickets/{id}/assignments",
        $protect($timeline->assignments(...)),
    );
};
