<?php

declare(strict_types=1);

use SkyFi\Portal\Controllers\PortalController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(PortalController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    // Dashboard & connection
    $router->add('GET', '/api/v1/portal/dashboard', ProtectRoute::wrap($auth, $controller->dashboard(...)));
    $router->add('GET', '/api/v1/portal/connection', ProtectRoute::wrap($auth, $controller->connection(...)));

    // Billing
    $router->add('GET', '/api/v1/portal/invoices', ProtectRoute::wrap($auth, $controller->invoices(...)));
    $router->add('GET', '/api/v1/portal/invoices/{id}', ProtectRoute::wrap($auth, $controller->invoice(...)));
    $router->add('GET', '/api/v1/portal/invoices/{id}/pdf', ProtectRoute::wrap($auth, $controller->invoicePdf(...)));
    $router->add('GET', '/api/v1/portal/balance', ProtectRoute::wrap($auth, $controller->balance(...)));

    // Payments
    $router->add('GET', '/api/v1/portal/payments', ProtectRoute::wrap($auth, $controller->payments(...)));
    $router->add('GET', '/api/v1/portal/payments/{id}', ProtectRoute::wrap($auth, $controller->payment(...)));
    $router->add('GET', '/api/v1/portal/payments/{id}/receipt', ProtectRoute::wrap($auth, $controller->paymentReceipt(...)));

    // Support
    $router->add('GET', '/api/v1/portal/tickets', ProtectRoute::wrap($auth, $controller->tickets(...)));
    $router->add('POST', '/api/v1/portal/tickets', ProtectRoute::wrap($auth, $controller->createTicket(...)));
    $router->add('GET', '/api/v1/portal/tickets/{id}', ProtectRoute::wrap($auth, $controller->ticket(...)));
    $router->add('POST', '/api/v1/portal/tickets/{id}/reply', ProtectRoute::wrap($auth, $controller->replyTicket(...)));
    $router->add('POST', '/api/v1/portal/tickets/{id}/close-request', ProtectRoute::wrap($auth, $controller->requestTicketClosure(...)));

    // Notifications
    $router->add('GET', '/api/v1/portal/notifications', ProtectRoute::wrap($auth, $controller->notifications(...)));
    $router->add('PATCH', '/api/v1/portal/notifications/{id}/read', ProtectRoute::wrap($auth, $controller->markNotificationRead(...)));
    $router->add('PATCH', '/api/v1/portal/notifications/read-all', ProtectRoute::wrap($auth, $controller->markAllNotificationsRead(...)));
    $router->add('PATCH', '/api/v1/portal/notifications/{id}/archive', ProtectRoute::wrap($auth, $controller->archiveNotification(...)));

    // Preferences
    $router->add('GET', '/api/v1/portal/preferences', ProtectRoute::wrap($auth, $controller->preferences(...)));
    $router->add('PUT', '/api/v1/portal/preferences', ProtectRoute::wrap($auth, $controller->updatePreferences(...)));

    // Profile
    $router->add('GET', '/api/v1/portal/profile', ProtectRoute::wrap($auth, $controller->profile(...)));
    $router->add('PUT', '/api/v1/portal/profile', ProtectRoute::wrap($auth, $controller->updateProfile(...)));
};
