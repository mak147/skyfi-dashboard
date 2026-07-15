<?php

declare(strict_types=1);

use SkyFi\Audit\Controllers\ActivityController;
use SkyFi\Audit\Controllers\AuditExportController;
use SkyFi\Audit\Controllers\AuditLogController;
use SkyFi\Audit\Controllers\ComplianceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Middleware\ProtectRoute;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $auditLogs = $container->get(AuditLogController::class);
    $activities = $container->get(ActivityController::class);
    $exports = $container->get(AuditExportController::class);
    $compliance = $container->get(ComplianceController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    // Audit Logs
    $router->add('GET', '/api/v1/audit/logs', ProtectRoute::wrap($auth, $auditLogs->index(...)));
    $router->add('GET', '/api/v1/audit/logs/{id}', ProtectRoute::wrap($auth, $auditLogs->show(...)));
    $router->add('GET', '/api/v1/audit/dashboard', ProtectRoute::wrap($auth, $auditLogs->dashboard(...)));
    $router->add('GET', '/api/v1/audit/filter-options', ProtectRoute::wrap($auth, $auditLogs->filterOptions(...)));
    $router->add('GET', '/api/v1/audit/resource-history', ProtectRoute::wrap($auth, $auditLogs->resourceHistory(...)));

    // Activity
    $router->add('GET', '/api/v1/audit/activity', ProtectRoute::wrap($auth, $activities->index(...)));
    $router->add('GET', '/api/v1/audit/users/{id}/activity', ProtectRoute::wrap($auth, $activities->userActivity(...)));

    // Exports
    $router->add('POST', '/api/v1/audit/export', ProtectRoute::wrap($auth, $exports->export(...)));
    $router->add('GET', '/api/v1/audit/exports', ProtectRoute::wrap($auth, $exports->index(...)));
    $router->add('GET', '/api/v1/audit/exports/{id}/download', ProtectRoute::wrap($auth, $exports->download(...)));

    // Compliance Policies
    $router->add('GET', '/api/v1/compliance/policies', ProtectRoute::wrap($auth, $compliance->listPolicies(...)));
    $router->add('POST', '/api/v1/compliance/policies', ProtectRoute::wrap($auth, $compliance->createPolicy(...)));
    $router->add('GET', '/api/v1/compliance/policies/{id}', ProtectRoute::wrap($auth, $compliance->getPolicy(...)));
    $router->add('PUT', '/api/v1/compliance/policies/{id}', ProtectRoute::wrap($auth, $compliance->updatePolicy(...)));
    $router->add('DELETE', '/api/v1/compliance/policies/{id}', ProtectRoute::wrap($auth, $compliance->deletePolicy(...)));

    // Retention Policies
    $router->add('GET', '/api/v1/compliance/retention', ProtectRoute::wrap($auth, $compliance->listRetentionPolicies(...)));
    $router->add('POST', '/api/v1/compliance/retention', ProtectRoute::wrap($auth, $compliance->createRetentionPolicy(...)));
    $router->add('GET', '/api/v1/compliance/retention/{id}', ProtectRoute::wrap($auth, $compliance->getRetentionPolicy(...)));
    $router->add('PUT', '/api/v1/compliance/retention/{id}', ProtectRoute::wrap($auth, $compliance->updateRetentionPolicy(...)));
    $router->add('DELETE', '/api/v1/compliance/retention/{id}', ProtectRoute::wrap($auth, $compliance->deleteRetentionPolicy(...)));
};
