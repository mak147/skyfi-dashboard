<?php

declare(strict_types=1);
namespace SkyFi\Support\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{Request, Response};
use SkyFi\Support\Contracts\TicketRepositoryContract;
final class TicketTimelineController
{
    public function __construct(
        private readonly TicketRepositoryContract $repo,
        private readonly RequirePermissionMiddleware $auth,
    ) {}
    public function timeline(Request $r): Response
    {
        $this->can($r);
        return new Response(200, [
            "data" => $this->repo->timeline($this->id($r)),
        ]);
    }
    public function assignments(Request $r): Response
    {
        $this->can($r);
        return new Response(200, [
            "data" => $this->repo->assignments($this->id($r)),
        ]);
    }
    private function can(Request $r): void
    {
        $this->auth->authorize(
            (int) ($r->attributes()["claims"]["sub"] ?? 0),
            "support.view",
        );
    }
    private function id(Request $r): int
    {
        return (int) ($r->attributes()["route_params"]["id"] ?? 0);
    }
}
