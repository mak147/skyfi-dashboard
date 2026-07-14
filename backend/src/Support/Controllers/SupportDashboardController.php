<?php

declare(strict_types=1);
namespace SkyFi\Support\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{Request, Response};
use SkyFi\Support\Contracts\SupportDashboardServiceContract;
final class SupportDashboardController
{
    public function __construct(
        private readonly SupportDashboardServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}
    public function dashboard(Request $r): Response
    {
        $this->can($r, "support.view");
        return new Response(200, ["data" => $this->service->dashboard()]);
    }
    public function sla(Request $r): Response
    {
        $this->can($r, "support.view");
        return new Response(200, ["data" => $this->service->slaDashboard()]);
    }
    public function process(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        return new Response(200, [
            "data" => ["processed" => $this->service->process($u)],
        ]);
    }
    private function can(Request $r, string $p): int
    {
        $u = (int) ($r->attributes()["claims"]["sub"] ?? 0);
        $this->auth->authorize($u, $p);
        return $u;
    }
}
