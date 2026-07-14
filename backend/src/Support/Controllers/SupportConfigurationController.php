<?php

declare(strict_types=1);
namespace SkyFi\Support\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{ApiResponse, Request, Response};
use SkyFi\Support\Contracts\TicketRepositoryContract;
final class SupportConfigurationController
{
    public function __construct(
        private readonly TicketRepositoryContract $repo,
        private readonly RequirePermissionMiddleware $auth,
    ) {}
    public function index(Request $r): Response
    {
        $this->can($r, "support.view");
        $x = match ($this->resource($r)) {
            "categories" => $this->repo->categories(),
            "teams" => $this->repo->teams(),
            "sla-policies" => $this->repo->slaPolicies(),
            default => [],
        };
        return new Response(200, ["data" => $x]);
    }
    public function store(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        return new Response(201, [
            "data" => $this->repo->saveConfiguration(
                $this->resource($r),
                null,
                $r->body(),
                $u,
            ),
        ]);
    }
    public function update(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        return new Response(200, [
            "data" => $this->repo->saveConfiguration(
                $this->resource($r),
                (int) ($r->attributes()["route_params"]["configId"] ?? 0),
                $r->body(),
                $u,
            ),
        ]);
    }
    public function destroy(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        $this->repo->deleteConfiguration(
            $this->resource($r),
            (int) ($r->attributes()["route_params"]["configId"] ?? 0),
            $u,
        );
        return ApiResponse::noContent();
    }
    private function resource(Request $r): string
    {
        return (string) ($r->attributes()["route_params"]["resource"] ?? "");
    }
    private function can(Request $r, string $p): int
    {
        $u = (int) ($r->attributes()["claims"]["sub"] ?? 0);
        $this->auth->authorize($u, $p);
        return $u;
    }
}
