<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Controllers;

use SkyFi\Vendors\Services\VendorDashboardService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class VendorDashboardController
{
    public function __construct(
        private readonly VendorDashboardService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function dashboard(Request $request): Response
    {
        $actor = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($actor, 'vendors.view');
        return new Response(200, ['data' => $this->service->getDashboardWidgets()]);
    }
}
