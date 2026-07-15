<?php

declare(strict_types=1);

namespace SkyFi\Vendors\Controllers;

use SkyFi\Vendors\DTOs\VendorQuotationData;
use SkyFi\Vendors\Services\VendorQuotationService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class VendorQuotationController
{
    public function __construct(
        private readonly VendorQuotationService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->can($request, 'vendors.view');
        $vendorId = $this->vendorId($request);
        $items = $this->service->list($vendorId > 0 ? $vendorId : null);
        return new Response(200, [
            'data' => array_map(static fn($q): array => ['type' => 'vendor-quotations', 'id' => (string) $q->id(), 'attributes' => $q->toArray()], $items),
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
        $item = $this->service->create(VendorQuotationData::fromArray($body), $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('vendor-quotations', (string) $item->id(), $item->toArray(), 201);
    }

    public function updateStatus(Request $request): Response
    {
        $actor = $this->can($request, 'vendors.update');
        $body = $request->body();
        $status = (string) ($body['status'] ?? 'received');
        $item = $this->service->updateStatus($this->id($request), $status, $actor, $request->ipAddress(), $request->userAgent());
        return ApiResponse::resource('vendor-quotations', (string) $item->id(), $item->toArray());
    }

    public function destroy(Request $request): Response
    {
        $actor = $this->can($request, 'vendors.delete');
        $this->service->delete($this->id($request), $actor, $request->ipAddress(), $request->userAgent());
        return new Response(204);
    }

    private function can(Request $request, string $permission): int
    {
        $actor = (int) ($request->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($actor, $permission);
        return $actor;
    }

    private function id(Request $request): int
    {
        return (int) ($request->attributes()['route_params']['quotationId'] ?? $request->attributes()['route_params']['id'] ?? 0);
    }

    private function vendorId(Request $request): int
    {
        return (int) ($request->attributes()['route_params']['id'] ?? $request->attributes()['route_params']['vendorId'] ?? 0);
    }
}
