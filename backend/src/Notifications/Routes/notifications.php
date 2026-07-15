<?php

declare(strict_types=1);

use SkyFi\Notifications\Controllers\DeliveryHistoryController;
use SkyFi\Notifications\Controllers\NotificationController;
use SkyFi\Notifications\Controllers\NotificationEventController;
use SkyFi\Notifications\Controllers\NotificationTemplateController;
use SkyFi\Notifications\Controllers\UserPreferenceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
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

    // Catalog & dispatch
    $router->add('GET', '/api/v1/notifications/catalog', ProtectRoute::wrap($auth, $notifications->catalog(...)));
    $router->add('POST', '/api/v1/notifications/dispatch', ProtectRoute::wrap($auth, $notifications->dispatch(...)));

    // Preferences
    $router->add('GET', '/api/v1/notifications/preferences', ProtectRoute::wrap($auth, $preferences->show(...)));
    $router->add('PUT', '/api/v1/notifications/preferences', ProtectRoute::wrap($auth, $preferences->update(...)));

    // Templates
    $router->add('GET', '/api/v1/notifications/templates', ProtectRoute::wrap($auth, $templates->index(...)));
    $router->add('POST', '/api/v1/notifications/templates', ProtectRoute::wrap($auth, $templates->store(...)));
    $router->add('GET', '/api/v1/notifications/templates/{id}', ProtectRoute::wrap($auth, $templates->show(...)));
    $router->add('PUT', '/api/v1/notifications/templates/{id}', ProtectRoute::wrap($auth, $templates->update(...)));
    $router->add('DELETE', '/api/v1/notifications/templates/{id}', ProtectRoute::wrap($auth, $templates->destroy(...)));
    $router->add('POST', '/api/v1/notifications/templates/{id}/preview', ProtectRoute::wrap($auth, $templates->preview(...)));

    // Deliveries
    $router->add('GET', '/api/v1/notifications/deliveries', ProtectRoute::wrap($auth, $deliveries->index(...)));
    $router->add('GET', '/api/v1/notifications/deliveries/{id}', ProtectRoute::wrap($auth, $deliveries->show(...)));

    // Events
    $router->add('GET', '/api/v1/notifications/events', ProtectRoute::wrap($auth, $events->index(...)));
    $router->add('GET', '/api/v1/notifications/events/{id}', ProtectRoute::wrap($auth, $events->show(...)));

    // Inbox
    $router->add('GET', '/api/v1/notifications/unread-count', ProtectRoute::wrap($auth, $notifications->unreadCount(...)));
    $router->add('PATCH', '/api/v1/notifications/read-all', ProtectRoute::wrap($auth, $notifications->markAllRead(...)));
    $router->add('GET', '/api/v1/notifications', ProtectRoute::wrap($auth, $notifications->index(...)));
    $router->add('GET', '/api/v1/notifications/{id}', ProtectRoute::wrap($auth, $notifications->show(...)));
    $router->add('PATCH', '/api/v1/notifications/{id}/read', ProtectRoute::wrap($auth, $notifications->markRead(...)));
    $router->add('PATCH', '/api/v1/notifications/{id}/archive', ProtectRoute::wrap($auth, $notifications->archive(...)));
    $router->add('DELETE', '/api/v1/notifications/{id}', ProtectRoute::wrap($auth, $notifications->destroy(...)));
};
