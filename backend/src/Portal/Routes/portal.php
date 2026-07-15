<?php

declare(strict_types=1);

use SkyFi\Portal\Controllers\PortalController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $controller = $container->get(PortalController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // Dashboard & connection
    $router->add('GET', '/api/v1/portal/dashboard', $protect($controller->dashboard(...)));
    $router->add('GET', '/api/v1/portal/connection', $protect($controller->connection(...)));

    // Billing
    $router->add('GET', '/api/v1/portal/invoices', $protect($controller->invoices(...)));
    $router->add('GET', '/api/v1/portal/invoices/{id}', $protect($controller->invoice(...)));
    $router->add('GET', '/api/v1/portal/invoices/{id}/pdf', $protect($controller->invoicePdf(...)));
    $router->add('GET', '/api/v1/portal/balance', $protect($controller->balance(...)));

    // Payments
    $router->add('GET', '/api/v1/portal/payments', $protect($controller->payments(...)));
    $router->add('GET', '/api/v1/portal/payments/{id}', $protect($controller->payment(...)));
    $router->add('GET', '/api/v1/portal/payments/{id}/receipt', $protect($controller->paymentReceipt(...)));

    // Support
    $router->add('GET', '/api/v1/portal/tickets', $protect($controller->tickets(...)));
    $router->add('POST', '/api/v1/portal/tickets', $protect($controller->createTicket(...)));
    $router->add('GET', '/api/v1/portal/tickets/{id}', $protect($controller->ticket(...)));
    $router->add('POST', '/api/v1/portal/tickets/{id}/reply', $protect($controller->replyTicket(...)));
    $router->add('POST', '/api/v1/portal/tickets/{id}/close-request', $protect($controller->requestTicketClosure(...)));

    // Notifications
    $router->add('GET', '/api/v1/portal/notifications', $protect($controller->notifications(...)));
    $router->add('PATCH', '/api/v1/portal/notifications/{id}/read', $protect($controller->markNotificationRead(...)));
    $router->add('PATCH', '/api/v1/portal/notifications/read-all', $protect($controller->markAllNotificationsRead(...)));
    $router->add('PATCH', '/api/v1/portal/notifications/{id}/archive', $protect($controller->archiveNotification(...)));

    // Preferences
    $router->add('GET', '/api/v1/portal/preferences', $protect($controller->preferences(...)));
    $router->add('PUT', '/api/v1/portal/preferences', $protect($controller->updatePreferences(...)));

    // Profile
    $router->add('GET', '/api/v1/portal/profile', $protect($controller->profile(...)));
    $router->add('PUT', '/api/v1/portal/profile', $protect($controller->updateProfile(...)));
};
