<?php

declare(strict_types=1);

namespace SkyFi\Audit\Controllers;

use SkyFi\Audit\Contracts\AuditServiceContract;
use SkyFi\Audit\DTOs\ExportRequestData;
use SkyFi\Audit\Validators\AuditValidator;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class AuditExportController
{
    public function __construct(
        private readonly AuditServiceContract $service,
        private readonly AuditValidator $validator,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function export(Request $r): Response
    {
        $userId = $this->can($r, 'audit.export');
        $body = $r->body();
        $this->validator->validateExport($body);

        $data = ExportRequestData::fromArray($body);
        $result = $this->service->requestExport($userId, $data);

        return new Response(201, [
            'data' => [
                'type' => 'audit-exports',
                'id' => (string) ($result['id'] ?? ''),
                'attributes' => $result,
            ],
        ]);
    }

    public function index(Request $r): Response
    {
        $userId = $this->can($r, 'audit.export');
        $exports = $this->service->getExportHistory($userId);

        return new Response(200, [
            'data' => array_map(
                static fn(array $item) => [
                    'type' => 'audit-exports',
                    'id' => (string) ($item['id'] ?? ''),
                    'attributes' => $item,
                ],
                $exports,
            ),
        ]);
    }

    public function download(Request $r): Response
    {
        $this->can($r, 'audit.export');
        $params = $r->attributes()['route_params'] ?? [];
        $id = (int) ($params['id'] ?? 0);
        $export = $this->service->getExport($id);

        if (($export['status'] ?? '') !== 'completed' || empty($export['file_path'])) {
            return new Response(404, [
                'errors' => [[
                    'status' => '404',
                    'code' => 'export_not_ready',
                    'title' => 'Not Found',
                    'detail' => 'Export file is not ready or does not exist.',
                ]],
            ]);
        }

        return new Response(200, [
            'data' => [
                'type' => 'audit-export-downloads',
                'id' => (string) $id,
                'attributes' => $export,
            ],
        ]);
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);
        return $userId;
    }
}
