<?php

declare(strict_types=1);

use SkyFi\Audit\Controllers\ActivityController;
use SkyFi\Audit\Controllers\AuditExportController;
use SkyFi\Audit\Controllers\AuditLogController;
use SkyFi\Audit\Controllers\ComplianceController;
use SkyFi\Shared\Http\Middleware\JwtAuthMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Router;
use SkyFi\Shared\Providers\Container;

return static function (Router $router, Container $container): void {
    $auditLogs = $container->get(AuditLogController::class);
    $activities = $container->get(ActivityController::class);
    $exports = $container->get(AuditExportController::class);
    $compliance = $container->get(ComplianceController::class);
    $auth = $container->get(JwtAuthMiddleware::class);

    $protect = static function (callable $handler) use ($auth): callable {
        return static function (Request $request) use ($auth, $handler) {
            $attributes = $request->attributes();
            $attributes['claims'] = $auth->authenticate($request);

            return $handler($request->withAttributes($attributes));
        };
    };

    // Audit Logs
    $router->add('GET', '/api/v1/audit/logs', $protect($auditLogs->index(...)));
    $router->add('GET', '/api/v1/audit/logs/{id}', $protect($auditLogs->show(...)));
    $router->add('GET', '/api/v1/audit/dashboard', $protect($auditLogs->dashboard(...)));
    $router->add('GET', '/api/v1/audit/filter-options', $protect($auditLogs->filterOptions(...)));
    $router->add('GET', '/api/v1/audit/resource-history', $protect($auditLogs->resourceHistory(...)));

    // Activity
    $router->add('GET', '/api/v1/audit/activity', $protect($activities->index(...)));
    $router->add('GET', '/api/v1/audit/users/{id}/activity', $protect($activities->userActivity(...)));

    // Exports
    $router->add('POST', '/api/v1/audit/export', $protect($exports->export(...)));
    $router->add('GET', '/api/v1/audit/exports', $protect($exports->index(...)));
    $router->add('GET', '/api/v1/audit/exports/{id}/download', $protect($exports->download(...)));

    // Compliance Policies
    $router->add('GET', '/api/v1/compliance/policies', $protect($compliance->listPolicies(...)));
    $router->add('POST', '/api/v1/compliance/policies', $protect($compliance->createPolicy(...)));
    $router->add('GET', '/api/v1/compliance/policies/{id}', $protect($compliance->getPolicy(...)));
    $router->add('PUT', '/api/v1/compliance/policies/{id}', $protect($compliance->updatePolicy(...)));
    $router->add('DELETE', '/api/v1/compliance/policies/{id}', $protect($compliance->deletePolicy(...)));

    // Retention Policies
    $router->add('GET', '/api/v1/compliance/retention', $protect($compliance->listRetentionPolicies(...)));
    $router->add('POST', '/api/v1/compliance/retention', $protect($compliance->createRetentionPolicy(...)));
    $router->add('GET', '/api/v1/compliance/retention/{id}', $protect($compliance->getRetentionPolicy(...)));
    $router->add('PUT', '/api/v1/compliance/retention/{id}', $protect($compliance->updateRetentionPolicy(...)));
    $router->add('DELETE', '/api/v1/compliance/retention/{id}', $protect($compliance->deleteRetentionPolicy(...)));
};
