<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Controllers;

use SkyFi\Infrastructure\Contracts\PopSiteServiceContract;
use SkyFi\Infrastructure\Data\CreatePopSiteData;
use SkyFi\Infrastructure\Data\PopSiteListFilters;
use SkyFi\Infrastructure\Data\UpdatePopSiteData;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;

final class PopSiteController
{
    public function __construct(
        private readonly PopSiteServiceContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $filters = $this->parseFilters($request);
        $result = $this->service->list($filters);

        return Response::json([
            'data' => array_map(fn($site) => ['type' => 'pop-sites', 'id' => (string) $site->id, 'attributes' => $site->toArray()], $result['items']),
            'meta' => [
                'current_page' => $result['page'],
                'per_page' => $result['perPage'],
                'total' => $result['total'],
                'last_page' => $result['lastPage'],
            ],
        ]);
    }

    public function store(Request $request): Response
    {
        $claims = $request->getAttribute('claims');
        $this->permissionMiddleware->authorize($claims['sub'], 'infrastructure.create');

        $data = $request->getParsedBody();
        $createData = $this->parseCreateData($data);

        $popSite = $this->service->create(
            $createData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'pop-sites', 'id' => (string) $popSite->id, 'attributes' => $popSite->toArray()],
        ], 201);
    }

    public function show(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $popSite = $this->service->get($id);

        return Response::json([
            'data' => ['type' => 'pop-sites', 'id' => (string) $popSite->id, 'attributes' => $popSite->toArray()],
        ]);
    }

    public function update(Request $request): Response
    {
        $claims = $request->getAttribute('claims');
        $this->permissionMiddleware->authorize($claims['sub'], 'infrastructure.update');

        $id = (int) $request->getAttribute('route_params')['id'];
        $data = $request->getParsedBody();
        $updateData = $this->parseUpdateData($data);

        $popSite = $this->service->update(
            $id,
            $updateData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'pop-sites', 'id' => (string) $popSite->id, 'attributes' => $popSite->toArray()],
        ]);
    }

    public function destroy(Request $request): Response
    {
        $claims = $request->getAttribute('claims');
        $this->permissionMiddleware->authorize($claims['sub'], 'infrastructure.delete');

        $id = (int) $request->getAttribute('route_params')['id'];

        $this->service->delete(
            $id,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json(null, 204);
    }

    public function changeStatus(Request $request): Response
    {
        $claims = $request->getAttribute('claims');
        $this->permissionMiddleware->authorize($claims['sub'], 'infrastructure.manage');

        $id = (int) $request->getAttribute('route_params')['id'];
        $data = $request->getParsedBody();

        if (!isset($data['status'])) {
            throw new ValidationException([
                ['code' => 'required', 'detail' => 'Status is required.', 'source' => ['pointer' => '/data/attributes/status']],
            ]);
        }

        $popSite = $this->service->changeStatus(
            $id,
            (string) $data['status'],
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'pop-sites', 'id' => (string) $popSite->id, 'attributes' => $popSite->toArray()],
        ]);
    }

    public function towers(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $towers = $this->service->getTowers($id);

        return Response::json([
            'data' => array_map(fn($tower) => ['type' => 'towers', 'id' => (string) $tower->id, 'attributes' => $tower->toArray()], $towers),
        ]);
    }

    public function mapPoints(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $points = $this->service->getMapPoints();

        return Response::json([
            'data' => $points,
        ]);
    }

    private function parseFilters(Request $request): PopSiteListFilters
    {
        $query = $request->getQueryParams();

        return new PopSiteListFilters(
            search: $query['search'] ?? null,
            status: $query['status'] ?? null,
            city: $query['city'] ?? null,
            region: $query['region'] ?? null,
            powerStatus: $query['power_status'] ?? null,
            page: isset($query['page']) ? max(1, (int) $query['page']) : 1,
            perPage: isset($query['per_page']) ? min(max(1, (int) $query['per_page']), 100) : 15,
            sort: $query['sort'] ?? '-created_at',
        );
    }

    private function parseCreateData(array $data): CreatePopSiteData
    {
        return new CreatePopSiteData(
            name: (string) ($data['name'] ?? ''),
            code: (string) ($data['code'] ?? ''),
            addressLine1: $data['address_line1'] ?? null,
            addressLine2: $data['address_line2'] ?? null,
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            country: $data['country'] ?? null,
            gpsLatitude: $data['gps_latitude'] ?? null,
            gpsLongitude: $data['gps_longitude'] ?? null,
            contactPerson: $data['contact_person'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            powerStatus: (string) ($data['power_status'] ?? 'unknown'),
            fiberProvider: $data['fiber_provider'] ?? null,
            status: (string) ($data['status'] ?? 'planning'),
            notes: $data['notes'] ?? null,
        );
    }

    private function parseUpdateData(array $data): UpdatePopSiteData
    {
        return new UpdatePopSiteData(
            name: $data['name'] ?? null,
            code: $data['code'] ?? null,
            addressLine1: $data['address_line1'] ?? null,
            addressLine2: $data['address_line2'] ?? null,
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            country: $data['country'] ?? null,
            gpsLatitude: $data['gps_latitude'] ?? null,
            gpsLongitude: $data['gps_longitude'] ?? null,
            contactPerson: $data['contact_person'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            powerStatus: $data['power_status'] ?? null,
            fiberProvider: $data['fiber_provider'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
