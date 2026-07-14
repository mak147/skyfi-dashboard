<?php

declare(strict_types=1);
namespace SkyFi\Support\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{Request, Response};
use SkyFi\Support\Contracts\TicketRepositoryContract;
final class SupportLookupController
{
    public function __construct(
        private readonly TicketRepositoryContract $repo,
        private readonly RequirePermissionMiddleware $auth,
    ) {}
    public function lookup(Request $r): Response
    {
        $this->auth->authorize(
            (int) ($r->attributes()["claims"]["sub"] ?? 0),
            "support.view",
        );
        $resource =
            (string) ($r->attributes()["route_params"]["resource"] ?? "");
        return new Response(200, [
            "data" => $this->repo->lookup(
                $resource,
                trim((string) ($r->query()["search"] ?? "")),
                isset($r->query()["customer_id"])
                    ? (int) $r->query()["customer_id"]
                    : null,
            ),
        ]);
    }
}
