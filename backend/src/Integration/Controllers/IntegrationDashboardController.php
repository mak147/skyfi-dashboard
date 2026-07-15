<?php

declare(strict_types=1);

namespace SkyFi\Integration\Controllers;

use SkyFi\Integration\Contracts\IntegrationServiceContract;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;

final class IntegrationDashboardController
{
    public function __construct(
        private readonly IntegrationServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function show(Request $r): Response
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, 'integration.view');
        $data = $this->service->dashboard();

        return new \SkyFi\Shared\Http\Response(200, [
            'data' => ['type' => 'integration-dashboard', 'id' => 'dashboard', 'attributes' => $data],
        ]);
    }
}
