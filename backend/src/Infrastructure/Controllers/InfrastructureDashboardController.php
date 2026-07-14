<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Controllers;

use SkyFi\Infrastructure\Contracts\InfrastructureDashboardContract;
use SkyFi\Infrastructure\Data\InfrastructureDashboardPayload;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;

final class InfrastructureDashboardController
{
    public function __construct(
        private readonly InfrastructureDashboardContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function summary(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $payload = $this->service->getSummary();

        return Response::json([
            'data' => $payload->toArray(),
        ]);
    }
}
