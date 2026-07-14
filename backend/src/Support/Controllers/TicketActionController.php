<?php

declare(strict_types=1);
namespace SkyFi\Support\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{ApiResponse, Request, Response};
use SkyFi\Support\Contracts\TicketServiceContract;
use SkyFi\Support\DTOs\{
    AssignmentData,
    EscalationData,
    MergeTicketsData,
    SplitTicketData,
};
final class TicketActionController
{
    public function __construct(
        private readonly TicketServiceContract $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}
    public function assign(Request $r): Response
    {
        $u = $this->can($r, "support.assign");
        return $this->ticket(
            $this->service->assign(
                $this->id($r),
                AssignmentData::fromArray($r->body()),
                $u,
            ),
        );
    }
    public function status(Request $r): Response
    {
        $b = $r->body();
        $s = (string) ($b["status"] ?? "");
        $permission = in_array($s, ["resolved", "closed", "open"], true)
            ? "support.close"
            : (in_array($s, ["escalated", "cancelled"], true)
                ? "support.manage"
                : "support.update");
        $u = $this->can($r, $permission);
        return $this->ticket(
            $this->service->transition(
                $this->id($r),
                $s,
                $u,
                isset($b["resolution"]) ? (string) $b["resolution"] : null,
                isset($b["root_cause"]) ? (string) $b["root_cause"] : null,
            ),
        );
    }
    public function resolve(Request $r): Response
    {
        return $this->fixed($r, "resolved");
    }
    public function close(Request $r): Response
    {
        return $this->fixed($r, "closed");
    }
    public function reopen(Request $r): Response
    {
        $u = $this->can($r, "support.close");
        $b = $r->body();
        return $this->ticket(
            $this->service->transition(
                $this->id($r),
                (string) ($b["status"] ?? "open"),
                $u,
            ),
        );
    }
    public function cancel(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        return $this->ticket(
            $this->service->transition($this->id($r), "cancelled", $u),
        );
    }
    public function escalate(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        return $this->ticket(
            $this->service->escalate(
                $this->id($r),
                EscalationData::fromArray($r->body()),
                $u,
            ),
        );
    }
    public function merge(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        return $this->ticket(
            $this->service->merge(
                $this->id($r),
                MergeTicketsData::fromArray($r->body()),
                $u,
            ),
        );
    }
    public function split(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        return $this->ticket(
            $this->service->split(
                $this->id($r),
                SplitTicketData::fromArray($r->body()),
                $u,
            ),
            201,
        );
    }
    private function fixed(Request $r, string $status): Response
    {
        $u = $this->can($r, "support.close");
        $b = $r->body();
        return $this->ticket(
            $this->service->transition(
                $this->id($r),
                $status,
                $u,
                isset($b["resolution"]) ? (string) $b["resolution"] : null,
                isset($b["root_cause"]) ? (string) $b["root_cause"] : null,
            ),
        );
    }
    private function ticket($t, int $code = 200): Response
    {
        return ApiResponse::resource(
            "support-tickets",
            (string) $t->id(),
            $t->toArray(),
            $code,
        );
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
