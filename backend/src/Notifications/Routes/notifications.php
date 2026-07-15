<?php

declare(strict_types=1);

use SkyFi\Notifications\Controllers\DeliveryHistoryController;
use SkyFi\Notifications\Controllers\NotificationController;
use SkyFi\Notifications\Controllers\NotificationEventController;
use SkyFi\Notifications\Controllers\NotificationTemplateController;
use SkyFi\Notifications\Controllers\UserPreferenceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $notifications = $container->get(NotificationController::class);
    $templates = $container->get(NotificationTemplateController::class);
    $preferences = $container->get(UserPreferenceController::class);
    $deliveries = $container->get(DeliveryHistoryController::class);
    $events = $container->get(NotificationEventController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // Catalog & dispatch
    $router->add('GET', '/api/v1/notifications/catalog', $protect($notifications->catalog(...)));
    $router->add('POST', '/api/v1/notifications/dispatch', $protect($notifications->dispatch(...)));

    // Preferences
    $router->add('GET', '/api/v1/notifications/preferences', $protect($preferences->show(...)));
    $router->add('PUT', '/api/v1/notifications/preferences', $protect($preferences->update(...)));

    // Templates
    $router->add('GET', '/api/v1/notifications/templates', $protect($templates->index(...)));
    $router->add('POST', '/api/v1/notifications/templates', $protect($templates->store(...)));
    $router->add('GET', '/api/v1/notifications/templates/{id}', $protect($templates->show(...)));
    $router->add('PUT', '/api/v1/notifications/templates/{id}', $protect($templates->update(...)));
    $router->add('DELETE', '/api/v1/notifications/templates/{id}', $protect($templates->destroy(...)));
    $router->add('POST', '/api/v1/notifications/templates/{id}/preview', $protect($templates->preview(...)));

    // Deliveries
    $router->add('GET', '/api/v1/notifications/deliveries', $protect($deliveries->index(...)));
    $router->add('GET', '/api/v1/notifications/deliveries/{id}', $protect($deliveries->show(...)));

    // Events
    $router->add('GET', '/api/v1/notifications/events', $protect($events->index(...)));
    $router->add('GET', '/api/v1/notifications/events/{id}', $protect($events->show(...)));

    // Inbox
    $router->add('GET', '/api/v1/notifications/unread-count', $protect($notifications->unreadCount(...)));
    $router->add('PATCH', '/api/v1/notifications/read-all', $protect($notifications->markAllRead(...)));
    $router->add('GET', '/api/v1/notifications', $protect($notifications->index(...)));
    $router->add('GET', '/api/v1/notifications/{id}', $protect($notifications->show(...)));
    $router->add('PATCH', '/api/v1/notifications/{id}/read', $protect($notifications->markRead(...)));
    $router->add('PATCH', '/api/v1/notifications/{id}/archive', $protect($notifications->archive(...)));
    $router->add('DELETE', '/api/v1/notifications/{id}', $protect($notifications->destroy(...)));
};
