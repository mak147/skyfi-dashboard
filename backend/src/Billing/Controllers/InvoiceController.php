<?php

declare(strict_types=1);

namespace SkyFi\Billing\Controllers;

use SkyFi\Billing\Contracts\InvoiceServiceContract;
use SkyFi\Billing\Data\BulkGenerateData;
use SkyFi\Billing\Data\CreateInvoiceData;
use SkyFi\Billing\Data\GenerateInvoiceData;
use SkyFi\Billing\Data\InvoiceListFilters;
use SkyFi\Billing\Data\UpdateInvoiceData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class InvoiceController
{
    public function __construct(
        private readonly InvoiceServiceContract $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    private function getUserIdFromRequest(Request $request): int
    {
        $claims = $request->attributes()['claims'] ?? null;
        return $claims && isset($claims['sub']) ? (int) $claims['sub'] : 0;
    }

    public function index(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.view');

        $filters = InvoiceListFilters::fromQuery($request->query());
        $result = $this->service->list($filters);

        $data = array_map(
            static fn($invoice): array => [
                'type' => 'invoices',
                'id' => (string) $invoice->id,
                'attributes' => $invoice->toArray(),
            ],
            $result['items'],
        );

        $page = $result['page'];
        $perPage = $result['perPage'];
        $lastPage = $result['lastPage'];
        $total = $result['total'];

        $links = [
            'self' => '/api/v1/invoices?page[number]=' . $page . '&page[size]=' . $perPage,
            'first' => '/api/v1/invoices?page[number]=1&page[size]=' . $perPage,
            'last' => '/api/v1/invoices?page[number]=' . $lastPage . '&page[size]=' . $perPage,
        ];

        if ($page > 1) {
            $links['prev'] = '/api/v1/invoices?page[number]=' . ($page - 1) . '&page[size]=' . $perPage;
        }
        if ($page < $lastPage) {
            $links['next'] = '/api/v1/invoices?page[number]=' . ($page + 1) . '&page[size]=' . $perPage;
        }

        return new Response(200, [
            'data' => $data,
            'links' => $links,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }

    public function show(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.view');

        $params = $request->attributes()['route_params'] ?? [];
        $invoice = $this->service->get((int) ($params['id'] ?? 0));

        return ApiResponse::resource('invoices', (string) $invoice->id, $invoice->toArray());
    }

    public function store(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.create');

        $data = CreateInvoiceData::fromArray($request->body());
        $invoice = $this->service->create($data, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('invoices', (string) $invoice->id, $invoice->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.update');

        $params = $request->attributes()['route_params'] ?? [];
        $data = UpdateInvoiceData::fromArray($request->body());
        $invoice = $this->service->update((int) ($params['id'] ?? 0), $data, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('invoices', (string) $invoice->id, $invoice->toArray());
    }

    public function destroy(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.delete');

        $params = $request->attributes()['route_params'] ?? [];
        $this->service->delete((int) ($params['id'] ?? 0), $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    public function changeStatus(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.manage');

        $params = $request->attributes()['route_params'] ?? [];
        $body = $request->body();
        $status = isset($body['status']) && is_string($body['status']) ? $body['status'] : '';

        $invoice = $this->service->changeStatus((int) ($params['id'] ?? 0), $status, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('invoices', (string) $invoice->id, $invoice->toArray());
    }

    public function generate(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.generate');

        $data = GenerateInvoiceData::fromArray($request->body());
        $invoice = $this->service->generate($data, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('invoices', (string) $invoice->id, $invoice->toArray(), 201);
    }

    public function bulkGenerate(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.generate');

        $data = BulkGenerateData::fromArray($request->body());
        $result = $this->service->bulkGenerate($data, $userId, $request->ipAddress(), $request->userAgent());

        return new Response(200, ['data' => $result]);
    }

    public function statistics(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.view');

        return new Response(200, ['data' => $this->service->statistics()]);
    }

    public function activity(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'billing.view');

        $params = $request->attributes()['route_params'] ?? [];
        $activities = $this->service->activity((int) ($params['id'] ?? 0));

        return new Response(200, ['data' => $activities]);
    }
}
