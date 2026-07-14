<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Controllers;

use SkyFi\Hotspot\Contracts\VoucherServiceContract;
use SkyFi\Hotspot\DTOs\GenerateVoucherBatchData;
use SkyFi\Hotspot\DTOs\VoucherListFilters;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class VoucherController
{
    public function __construct(
        private readonly VoucherServiceContract $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize($request, 'hotspot.vouchers');
        $result = $this->service->listVouchers(VoucherListFilters::fromQuery($request->query()));

        return new Response(200, [
            'data' => array_map(static fn ($v): array => [
                'type' => 'hotspot-vouchers',
                'id' => (string) $v->id(),
                'attributes' => $v->toArray(),
            ], $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $this->authorize($request, 'hotspot.vouchers');
        $voucher = $this->service->getVoucher($this->routeId($request));

        return ApiResponse::resource('hotspot-vouchers', (string) $voucher->id(), $voucher->toArray());
    }

    public function generate(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.vouchers');
        $batch = $this->service->generateBatch(
            GenerateVoucherBatchData::fromArray($request->body()),
            $actorId,
            $request->ipAddress(),
            $request->userAgent()
        );

        return ApiResponse::resource('hotspot-voucher-batches', (string) $batch->id(), $batch->toArray(), 201);
    }

    public function batches(Request $request): Response
    {
        $this->authorize($request, 'hotspot.vouchers');
        $query = $request->query();
        $page = isset($query['page']) && is_array($query['page']) ? max(1, (int) ($query['page']['number'] ?? 1)) : max(1, (int) ($query['page'] ?? 1));
        $perPage = isset($query['page']) && is_array($query['page']) ? max(1, min(100, (int) ($query['page']['size'] ?? 15))) : 15;
        $status = isset($query['filter']) && is_array($query['filter']) ? ($query['filter']['status'] ?? null) : null;

        $result = $this->service->listBatches($page, $perPage, is_string($status) ? $status : null);

        return new Response(200, [
            'data' => array_map(static fn ($b): array => [
                'type' => 'hotspot-voucher-batches',
                'id' => (string) $b->id(),
                'attributes' => $b->toArray(),
            ], $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function revoke(Request $request): Response
    {
        $actorId = $this->authorize($request, 'hotspot.vouchers');
        $voucher = $this->service->revokeVoucher($this->routeId($request), $actorId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('hotspot-vouchers', (string) $voucher->id(), $voucher->toArray());
    }

    public function printBatch(Request $request): Response
    {
        $this->authorize($request, 'hotspot.vouchers');
        $batchId = (int) (($request->attributes()['route_params'] ?? [])['batchId'] ?? 0);

        $result = $this->service->printVouchers($batchId);

        return new Response(200, [
            'data' => [
                'type' => 'hotspot-voucher-print',
                'id' => (string) $batchId,
                'attributes' => $result,
            ],
        ]);
    }

    public function stats(Request $request): Response
    {
        $this->authorize($request, 'hotspot.vouchers');
        $stats = $this->service->getVoucherStats();

        return new Response(200, [
            'data' => [
                'type' => 'hotspot-voucher-stats',
                'id' => 'stats',
                'attributes' => $stats,
            ],
        ]);
    }

    private function authorize(Request $request, string $permission): int
    {
        $claims = $request->attributes()['claims'] ?? [];
        $userId = isset($claims['sub']) ? (int) $claims['sub'] : 0;
        $this->authorizer->authorize($userId, $permission);

        return $userId;
    }

    private function routeId(Request $request): int
    {
        return (int) (($request->attributes()['route_params'] ?? [])['id'] ?? 0);
    }
}
