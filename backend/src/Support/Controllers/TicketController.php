<?php

declare(strict_types=1);
namespace SkyFi\Support\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{ApiResponse, Request, Response};
use SkyFi\Support\Contracts\TicketServiceContract;
use SkyFi\Support\DTOs\{CreateTicketData, TicketListFilters, UpdateTicketData};
final class TicketController
{
    public function __construct(
        private readonly TicketServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}
    public function index(Request $r): Response
    {
        $this->can($r, "support.view");
        $x = $this->service->list(TicketListFilters::fromQuery($r->query()));
        return new Response(200, [
            "data" => array_map(
                fn($t) => [
                    "type" => "support-tickets",
                    "id" => (string) $t->id(),
                    "attributes" => $t->toArray(),
                ],
                $x["items"],
            ),
            "meta" => [
                "current_page" => $x["page"],
                "per_page" => $x["perPage"],
                "total" => $x["total"],
                "last_page" => $x["lastPage"],
            ],
        ]);
    }
    public function show(Request $r): Response
    {
        $this->can($r, "support.view");
        $x = $this->service->get($this->id($r));
        return ApiResponse::resource(
            "support-tickets",
            (string) $this->id($r),
            $x,
        );
    }
    public function store(Request $r): Response
    {
        $u = $this->can($r, "support.create");
        $t = $this->service->create(
            CreateTicketData::fromArray($r->body()),
            $u,
        );
        return ApiResponse::resource(
            "support-tickets",
            (string) $t->id(),
            $t->toArray(),
            201,
        );
    }
    public function update(Request $r): Response
    {
        $u = $this->can($r, "support.update");
        $t = $this->service->update(
            $this->id($r),
            UpdateTicketData::fromArray($r->body()),
            $u,
        );
        return ApiResponse::resource(
            "support-tickets",
            (string) $t->id(),
            $t->toArray(),
        );
    }
    public function destroy(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        $this->service->delete($this->id($r), $u);
        return ApiResponse::noContent();
    }
    private function can(Request $r, string $p): int
    {
        $u = (int) ($r->attributes()["claims"]["sub"] ?? 0);
        $this->auth->authorize($u, $p);
        return $u;
    }
    private function id(Request $r): int
    {
        return (int) ($r->attributes()["route_params"]["id"] ?? 0);
    }
}
