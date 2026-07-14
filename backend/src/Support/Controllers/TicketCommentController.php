<?php

declare(strict_types=1);
namespace SkyFi\Support\Controllers;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\{ApiResponse, Request, Response};
use SkyFi\Support\Contracts\{
    TicketCommentRepositoryContract,
    TicketServiceContract,
};
use SkyFi\Support\DTOs\CreateCommentData;
final class TicketCommentController
{
    public function __construct(
        private readonly TicketServiceContract $service,
        private readonly TicketCommentRepositoryContract $comments,
        private readonly RequirePermissionMiddleware $auth,
    ) {}
    public function index(Request $r): Response
    {
        $this->can($r, "support.view");
        return new Response(200, [
            "data" => array_map(
                fn($c) => $c->toArray(),
                $this->comments->list($this->id($r)),
            ),
        ]);
    }
    public function store(Request $r): Response
    {
        $u = $this->can($r, "support.update");
        $c = $this->service->comment(
            $this->id($r),
            CreateCommentData::fromArray($r->body()),
            $u,
        );
        return ApiResponse::resource(
            "ticket-comments",
            (string) $c->id(),
            $c->toArray(),
            201,
        );
    }
    public function update(Request $r): Response
    {
        $u = $this->can($r, "support.update");
        $body = trim((string) ($r->body()["body"] ?? ""));
        if ($body === "") {
            throw new \SkyFi\Shared\Exceptions\ValidationException([
                [
                    "code" => "body_required",
                    "detail" => "Comment body is required.",
                ],
            ]);
        }
        $c = $this->comments->update(
            $this->id($r),
            (int) ($r->attributes()["route_params"]["commentId"] ?? 0),
            $body,
            $u,
        );
        return ApiResponse::resource(
            "ticket-comments",
            (string) $c->id(),
            $c->toArray(),
        );
    }
    public function destroy(Request $r): Response
    {
        $u = $this->can($r, "support.manage");
        $this->comments->delete(
            $this->id($r),
            (int) ($r->attributes()["route_params"]["commentId"] ?? 0),
            $u,
        );
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
