<?php

declare(strict_types=1);

namespace SkyFi\Purchasing\Controllers;

use SkyFi\Purchasing\Services\PurchasingDashboardService;
use SkyFi\Purchasing\Services\PurchasingFinanceIntegrationService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class PurchasingDashboardController
{
    public function __construct(
        private readonly PurchasingDashboardService $service,
        private readonly PurchasingFinanceIntegrationService $finance,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function dashboard(Request $request): Response
    {
        $this->can($request, 'purchasing.view');
        return new Response(200, ['data' => $this->service->dashboard()]);
    }

    public function financePostings(Request $request): Response
    {
        $this->can($request, 'purchasing.manage');
        return new Response(200, ['data' => $this->finance->financePostings()]);
    }

    private function can(Request $request, string $permission): int
    {
        $actor = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($actor, $permission);
        return $actor;
    }
}
