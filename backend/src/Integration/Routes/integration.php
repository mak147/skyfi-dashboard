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
use SkyFi\Shared\Http\Middleware\ProtectRoute;
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

    // Dashboard
    $router->add('GET', '/api/v1/integration/dashboard', ProtectRoute::wrap($auth, $dashboard->show(...)));

    // API Keys
    $router->add('GET', '/api/v1/integration/api-keys', ProtectRoute::wrap($auth, $apiKeys->index(...)));
    $router->add('POST', '/api/v1/integration/api-keys', ProtectRoute::wrap($auth, $apiKeys->store(...)));
    $router->add('GET', '/api/v1/integration/api-keys/{id}', ProtectRoute::wrap($auth, $apiKeys->show(...)));
    $router->add('PUT', '/api/v1/integration/api-keys/{id}', ProtectRoute::wrap($auth, $apiKeys->update(...)));
    $router->add('DELETE', '/api/v1/integration/api-keys/{id}', ProtectRoute::wrap($auth, $apiKeys->destroy(...)));
    $router->add('POST', '/api/v1/integration/api-keys/{id}/regenerate', ProtectRoute::wrap($auth, $apiKeys->regenerate(...)));

    // Client Applications
    $router->add('GET', '/api/v1/integration/applications', ProtectRoute::wrap($auth, $applications->index(...)));
    $router->add('POST', '/api/v1/integration/applications', ProtectRoute::wrap($auth, $applications->store(...)));
    $router->add('GET', '/api/v1/integration/applications/{id}', ProtectRoute::wrap($auth, $applications->show(...)));
    $router->add('PUT', '/api/v1/integration/applications/{id}', ProtectRoute::wrap($auth, $applications->update(...)));
    $router->add('DELETE', '/api/v1/integration/applications/{id}', ProtectRoute::wrap($auth, $applications->destroy(...)));

    // Webhooks
    $router->add('GET', '/api/v1/integration/webhooks', ProtectRoute::wrap($auth, $webhooks->index(...)));
    $router->add('POST', '/api/v1/integration/webhooks', ProtectRoute::wrap($auth, $webhooks->store(...)));
    $router->add('GET', '/api/v1/integration/webhooks/{id}', ProtectRoute::wrap($auth, $webhooks->show(...)));
    $router->add('PUT', '/api/v1/integration/webhooks/{id}', ProtectRoute::wrap($auth, $webhooks->update(...)));
    $router->add('DELETE', '/api/v1/integration/webhooks/{id}', ProtectRoute::wrap($auth, $webhooks->destroy(...)));
    $router->add('POST', '/api/v1/integration/webhooks/{id}/rotate-secret', ProtectRoute::wrap($auth, $webhooks->rotateSecret(...)));
    $router->add('POST', '/api/v1/integration/webhooks/{id}/test', ProtectRoute::wrap($auth, $webhooks->test(...)));

    // Webhook Deliveries
    $router->add('GET', '/api/v1/integration/webhooks/{webhookId}/deliveries', ProtectRoute::wrap($auth, $deliveries->index(...)));
    $router->add('GET', '/api/v1/integration/deliveries', ProtectRoute::wrap($auth, $deliveries->index(...)));
    $router->add('GET', '/api/v1/integration/deliveries/{id}', ProtectRoute::wrap($auth, $deliveries->show(...)));
    $router->add('POST', '/api/v1/integration/deliveries/{id}/retry', ProtectRoute::wrap($auth, $deliveries->retry(...)));

    // Inbound Webhooks (no JWT auth — uses signature verification)
    $router->add('POST', '/api/v1/integration/webhooks/inbound', $inbound->handle(...));

    // Event Registry
    $router->add('GET', '/api/v1/integration/events', ProtectRoute::wrap($auth, $events->index(...)));
    $router->add('GET', '/api/v1/integration/events/{id}', ProtectRoute::wrap($auth, $events->show(...)));

    // Connectors
    $router->add('GET', '/api/v1/integration/connectors', ProtectRoute::wrap($auth, $connectors->index(...)));
    $router->add('GET', '/api/v1/integration/connectors/{type}', ProtectRoute::wrap($auth, $connectors->show(...)));
    $router->add('PUT', '/api/v1/integration/connectors/{type}', ProtectRoute::wrap($auth, $connectors->update(...)));
    $router->add('POST', '/api/v1/integration/connectors/{type}/test', ProtectRoute::wrap($auth, $connectors->test(...)));

    // Request Logs
    $router->add('GET', '/api/v1/integration/request-logs', ProtectRoute::wrap($auth, $requestLogs->index(...)));
};
