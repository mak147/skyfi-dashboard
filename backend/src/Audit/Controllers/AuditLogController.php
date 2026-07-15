<?php

declare(strict_types=1);

namespace SkyFi\Audit\Controllers;

use SkyFi\Audit\Contracts\AuditServiceContract;
use SkyFi\Audit\DTOs\AuditLogFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class AuditLogController
{
    public function __construct(
        private readonly AuditServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function index(Request $r): Response
    {
        $userId = $this->can($r, 'audit.view');
        $filters = AuditLogFilters::fromQuery($r->query());
        $result = $this->service->searchAuditLogs($filters);

        return new Response(200, [
            'data' => array_map(
                static fn(array $item) => [
                    'type' => 'audit-logs',
                    'id' => (string) ($item['id'] ?? ''),
                    'attributes' => $item,
                ],
                $result['items'],
            ),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function show(Request $r): Response
    {
        $this->can($r, 'audit.view');
        $log = $this->service->getAuditLog($this->id($r));

        return ApiResponse::resource('audit-logs', (string) ($log['id'] ?? ''), $log);
    }

    public function dashboard(Request $r): Response
    {
        $this->can($r, 'audit.view');
        $stats = $this->service->getDashboardStats();

        return new Response(200, [
            'data' => [
                'type' => 'audit-dashboard',
                'id' => 'dashboard',
                'attributes' => $stats,
            ],
        ]);
    }

    public function filterOptions(Request $r): Response
    {
        $this->can($r, 'audit.view');
        $options = $this->service->getFilterOptions();

        return new Response(200, [
            'data' => [
                'type' => 'audit-filter-options',
                'id' => 'filters',
                'attributes' => $options,
            ],
        ]);
    }

    public function resourceHistory(Request $r): Response
    {
        $this->can($r, 'audit.view');
        $query = $r->query();
        $entityType = (string) ($query['entity_type'] ?? '');
        $entityId = (int) ($query['entity_id'] ?? 0);

        if ($entityType === '' || $entityId <= 0) {
            return new Response(400, [
                'errors' => [[
                    'status' => '400',
                    'code' => 'missing_parameters',
                    'title' => 'Bad Request',
                    'detail' => 'entity_type and entity_id are required.',
                ]],
            ]);
        }

        $page = (int) ($query['page']['number'] ?? $query['page'] ?? 1);
        $perPage = (int) ($query['page']['size'] ?? $query['per_page'] ?? 25);
        $result = $this->service->getResourceHistory($entityType, $entityId, max(1, $page), max(1, min(100, $perPage)));

        return new Response(200, [
            'data' => array_map(
                static fn(array $item) => [
                    'type' => 'audit-logs',
                    'id' => (string) ($item['id'] ?? ''),
                    'attributes' => $item,
                ],
                $result['items'],
            ),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);
        return $userId;
    }

    private function id(Request $r): int
    {
        return (int) ($r->attributes()['route_params']['id'] ?? 0);
    }
}
