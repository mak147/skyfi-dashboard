<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Controllers;

use SkyFi\Infrastructure\Contracts\TowerServiceContract;
use SkyFi\Infrastructure\Data\CreateTowerData;
use SkyFi\Infrastructure\Data\TowerListFilters;
use SkyFi\Infrastructure\Data\UpdateTowerData;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;

final class TowerController
{
    public function __construct(
        private readonly TowerServiceContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $filters = $this->parseFilters($request);
        $result = $this->service->list($filters);

        return Response::json([
            'data' => array_map(fn($tower) => ['type' => 'towers', 'id' => (string) $tower->id, 'attributes' => $tower->toArray()], $result['items']),
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

        $tower = $this->service->create(
            $createData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'towers', 'id' => (string) $tower->id, 'attributes' => $tower->toArray()],
        ], 201);
    }

    public function show(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $tower = $this->service->get($id);

        return Response::json([
            'data' => ['type' => 'towers', 'id' => (string) $tower->id, 'attributes' => $tower->toArray()],
        ]);
    }

    public function update(Request $request): Response
    {
        $claims = $request->getAttribute('claims');
        $this->permissionMiddleware->authorize($claims['sub'], 'infrastructure.update');

        $id = (int) $request->getAttribute('route_params')['id'];
        $data = $request->getParsedBody();
        $updateData = $this->parseUpdateData($data);

        $tower = $this->service->update(
            $id,
            $updateData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'towers', 'id' => (string) $tower->id, 'attributes' => $tower->toArray()],
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

        $tower = $this->service->changeStatus(
            $id,
            (string) $data['status'],
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'towers', 'id' => (string) $tower->id, 'attributes' => $tower->toArray()],
        ]);
    }

    public function sectors(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $sectors = $this->service->getSectors($id);

        return Response::json([
            'data' => array_map(fn($sector) => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()], $sectors),
        ]);
    }

    public function devices(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $devices = $this->service->getDevices($id);

        return Response::json([
            'data' => array_map(fn($device) => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()], $devices),
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

    public function byPopSite(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $towers = $this->service->getByPopSite($id);

        return Response::json([
            'data' => array_map(fn($tower) => ['type' => 'towers', 'id' => (string) $tower->id, 'attributes' => $tower->toArray()], $towers),
        ]);
    }

    private function parseFilters(Request $request): TowerListFilters
    {
        $query = $request->getQueryParams();

        return new TowerListFilters(
            search: $query['search'] ?? null,
            status: $query['status'] ?? null,
            towerType: $query['tower_type'] ?? null,
            popSiteId: isset($query['pop_site_id']) ? (int) $query['pop_site_id'] : null,
            city: $query['city'] ?? null,
            region: $query['region'] ?? null,
            page: isset($query['page']) ? max(1, (int) $query['page']) : 1,
            perPage: isset($query['per_page']) ? min(max(1, (int) $query['per_page']), 100) : 15,
            sort: $query['sort'] ?? '-created_at',
        );
    }

    private function parseCreateData(array $data): CreateTowerData
    {
        return new CreateTowerData(
            popSiteId: (int) ($data['pop_site_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            code: $data['code'] ?? null,
            towerType: (string) ($data['tower_type'] ?? 'lattice'),
            heightMeters: $data['height_meters'] ?? null,
            owner: (string) ($data['owner'] ?? 'owned'),
            addressLine1: $data['address_line1'] ?? null,
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            gpsLatitude: $data['gps_latitude'] ?? null,
            gpsLongitude: $data['gps_longitude'] ?? null,
            status: (string) ($data['status'] ?? 'planning'),
            notes: $data['notes'] ?? null,
        );
    }

    private function parseUpdateData(array $data): UpdateTowerData
    {
        return new UpdateTowerData(
            popSiteId: isset($data['pop_site_id']) ? (int) $data['pop_site_id'] : null,
            name: $data['name'] ?? null,
            code: $data['code'] ?? null,
            towerType: $data['tower_type'] ?? null,
            heightMeters: $data['height_meters'] ?? null,
            owner: $data['owner'] ?? null,
            addressLine1: $data['address_line1'] ?? null,
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            gpsLatitude: $data['gps_latitude'] ?? null,
            gpsLongitude: $data['gps_longitude'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
