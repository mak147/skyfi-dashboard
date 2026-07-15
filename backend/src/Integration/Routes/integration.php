<?php

declare(strict_types=1);

use SkyFi\Integration\Controllers\ApiKeyController;
use SkyFi\Integration\Controllers\ClientApplicationController;
use SkyFi\Integration\Controllers\ConnectorController;
use SkyFi\Integration\Controllers\EventRegistryController;
use SkyFi\Integration\Controllers\InboundWebhookController;
use SkyFi\Integration\Controllers\IntegrationDashboardController;
use SkyFi\Integration\Controllers\RequestLogController;
use SkyFi\Integration\Controllers\WebhookController;
use SkyFi\Integration\Controllers\WebhookDeliveryController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $apiKeys = $container->get(ApiKeyController::class);
    $applications = $container->get(ClientApplicationController::class);
    $webhooks = $container->get(WebhookController::class);
    $deliveries = $container->get(WebhookDeliveryController::class);
    $events = $container->get(EventRegistryController::class);
    $connectors = $container->get(ConnectorController::class);
    $inbound = $container->get(InboundWebhookController::class);
    $requestLogs = $container->get(RequestLogController::class);
    $dashboard = $container->get(IntegrationDashboardController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // Dashboard
    $router->add('GET', '/api/v1/integration/dashboard', $protect($dashboard->show(...)));

    // API Keys
    $router->add('GET', '/api/v1/integration/api-keys', $protect($apiKeys->index(...)));
    $router->add('POST', '/api/v1/integration/api-keys', $protect($apiKeys->store(...)));
    $router->add('GET', '/api/v1/integration/api-keys/{id}', $protect($apiKeys->show(...)));
    $router->add('PUT', '/api/v1/integration/api-keys/{id}', $protect($apiKeys->update(...)));
    $router->add('DELETE', '/api/v1/integration/api-keys/{id}', $protect($apiKeys->destroy(...)));
    $router->add('POST', '/api/v1/integration/api-keys/{id}/regenerate', $protect($apiKeys->regenerate(...)));

    // Client Applications
    $router->add('GET', '/api/v1/integration/applications', $protect($applications->index(...)));
    $router->add('POST', '/api/v1/integration/applications', $protect($applications->store(...)));
    $router->add('GET', '/api/v1/integration/applications/{id}', $protect($applications->show(...)));
    $router->add('PUT', '/api/v1/integration/applications/{id}', $protect($applications->update(...)));
    $router->add('DELETE', '/api/v1/integration/applications/{id}', $protect($applications->destroy(...)));

    // Webhooks
    $router->add('GET', '/api/v1/integration/webhooks', $protect($webhooks->index(...)));
    $router->add('POST', '/api/v1/integration/webhooks', $protect($webhooks->store(...)));
    $router->add('GET', '/api/v1/integration/webhooks/{id}', $protect($webhooks->show(...)));
    $router->add('PUT', '/api/v1/integration/webhooks/{id}', $protect($webhooks->update(...)));
    $router->add('DELETE', '/api/v1/integration/webhooks/{id}', $protect($webhooks->destroy(...)));
    $router->add('POST', '/api/v1/integration/webhooks/{id}/rotate-secret', $protect($webhooks->rotateSecret(...)));
    $router->add('POST', '/api/v1/integration/webhooks/{id}/test', $protect($webhooks->test(...)));

    // Webhook Deliveries
    $router->add('GET', '/api/v1/integration/webhooks/{webhookId}/deliveries', $protect($deliveries->index(...)));
    $router->add('GET', '/api/v1/integration/deliveries', $protect($deliveries->index(...)));
    $router->add('GET', '/api/v1/integration/deliveries/{id}', $protect($deliveries->show(...)));
    $router->add('POST', '/api/v1/integration/deliveries/{id}/retry', $protect($deliveries->retry(...)));

    // Inbound Webhooks (no JWT auth — uses signature verification)
    $router->add('POST', '/api/v1/integration/webhooks/inbound', $inbound->handle(...));

    // Event Registry
    $router->add('GET', '/api/v1/integration/events', $protect($events->index(...)));
    $router->add('GET', '/api/v1/integration/events/{id}', $protect($events->show(...)));

    // Connectors
    $router->add('GET', '/api/v1/integration/connectors', $protect($connectors->index(...)));
    $router->add('GET', '/api/v1/integration/connectors/{type}', $protect($connectors->show(...)));
    $router->add('PUT', '/api/v1/integration/connectors/{type}', $protect($connectors->update(...)));
    $router->add('POST', '/api/v1/integration/connectors/{type}/test', $protect($connectors->test(...)));

    // Request Logs
    $router->add('GET', '/api/v1/integration/request-logs', $protect($requestLogs->index(...)));
};
