<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Controllers;

use SkyFi\Vendors\DTOs\VendorRatingData;
use SkyFi\Vendors\Services\VendorRatingService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class VendorRatingController
{
    public function __construct(
        private readonly VendorRatingService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'vendors.view');
        $vendorId = $this->vendorId($request);
        $items = $this->service->list($vendorId > 0 ? $vendorId : null);
        return new Response(200, [
            'data' => array_map(static fn($r): array => ['type' => 'vendor-ratings', 'id' => (string) $r->id(), 'attributes' => $r->toArray()], $items),
        ]);
    }

    public function store(Request $request): Response
    {
        $actor = $this->can($request, 'vendors.create');
        $body = $request->body();
        if (!isset($body['vendor_id']) || (int) $body['vendor_id'] <= 0) {
            $vid = $this->vendorId($request);
            if ($vid > 0) {
                $body['vendor_id'] = $vid;
            }
        }
        $item = $this->service->create(VendorRatingData::fromArray($body), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('vendor-ratings', (string) $item->id(), $item->toArray(), 201);
    }

    private function can(Request $request, string $permission): int
    {
        $actor = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($actor, $permission);
        return $actor;
    }

    private function vendorId(Request $request): int
    {
        return (int) ($request->attributes()['route_params']['id'] ?? $request->attributes()['route_params']['vendorId'] ?? 0);
    }
}
