<?php

declare(strict_types=1);

namespace SkyFi\Customers\Controllers;

use SkyFi\Customers\Contracts\CustomerServiceContract;
use SkyFi\Customers\Data\CreateCustomerData;
use SkyFi\Customers\Data\CustomerListFilters;
use SkyFi\Customers\Data\UpdateCustomerData;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class CustomerController
{
    public function __construct(
        private readonly CustomerServiceContract $service,
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
        $this->authorizer->authorize($userId, 'customers.view');

        $filters = CustomerListFilters::fromQuery($request->query());
        $result = $this->service->list($filters);

        $data = array_map(
            static fn ($customer): array => [
                'type' => 'customers',
                'id' => (string) $customer->id,
                'attributes' => $customer->toArray(),
            ],
            $result['items'],
        );

        $page = $result['page'];
        $perPage = $result['perPage'];
        $lastPage = $result['lastPage'];
        $total = $result['total'];

        $links = [
            'self' => '/api/v1/customers?page[number]=' . $page . '&page[size]=' . $perPage,
            'first' => '/api/v1/customers?page[number]=1&page[size]=' . $perPage,
            'last' => '/api/v1/customers?page[number]=' . $lastPage . '&page[size]=' . $perPage,
        ];

        if ($page > 1) {
            $links['prev'] = '/api/v1/customers?page[number]=' . ($page - 1) . '&page[size]=' . $perPage;
        }
        if ($page < $lastPage) {
            $links['next'] = '/api/v1/customers?page[number]=' . ($page + 1) . '&page[size]=' . $perPage;
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
        $this->authorizer->authorize($userId, 'customers.view');

        $params = $request->attributes()['route_params'] ?? [];
        $customer = $this->service->get((int) ($params['id'] ?? 0));

        return ApiResponse::resource('customers', (string) $customer->id, $customer->toArray());
    }

    public function store(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'customers.create');

        $data = CreateCustomerData::fromArray($request->body());
        $customer = $this->service->create($data, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('customers', (string) $customer->id, $customer->toArray(), 201);
    }

    public function update(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'customers.update');

        $params = $request->attributes()['route_params'] ?? [];
        $data = UpdateCustomerData::fromArray($request->body());
        $customer = $this->service->update((int) ($params['id'] ?? 0), $data, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('customers', (string) $customer->id, $customer->toArray());
    }

    public function destroy(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'customers.delete');

        $params = $request->attributes()['route_params'] ?? [];
        $this->service->delete((int) ($params['id'] ?? 0), $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::noContent();
    }

    public function changeStatus(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'customers.manage');

        $params = $request->attributes()['route_params'] ?? [];
        $body = $request->body();
        $status = isset($body['status']) && is_string($body['status']) ? $body['status'] : '';

        $customer = $this->service->changeStatus((int) ($params['id'] ?? 0), $status, $userId, $request->ipAddress(), $request->userAgent());

        return ApiResponse::resource('customers', (string) $customer->id, $customer->toArray());
    }
}
