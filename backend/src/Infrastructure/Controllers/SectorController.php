<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Controllers;

use SkyFi\Infrastructure\Contracts\SectorServiceContract;
use SkyFi\Infrastructure\Data\CreateSectorData;
use SkyFi\Infrastructure\Data\SectorListFilters;
use SkyFi\Infrastructure\Data\UpdateSectorData;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;

final class SectorController
{
    public function __construct(
        private readonly SectorServiceContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $filters = $this->parseFilters($request);
        $result = $this->service->list($filters);

        return Response::json([
            'data' => array_map(fn($sector) => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()], $result['items']),
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

        $sector = $this->service->create(
            $createData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()],
        ], 201);
    }

    public function show(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $sector = $this->service->get($id);

        return Response::json([
            'data' => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()],
        ]);
    }

    public function update(Request $request): Response
    {
        $claims = $request->getAttribute('claims');
        $this->permissionMiddleware->authorize($claims['sub'], 'infrastructure.update');

        $id = (int) $request->getAttribute('route_params')['id'];
        $data = $request->getParsedBody();
        $updateData = $this->parseUpdateData($data);

        $sector = $this->service->update(
            $id,
            $updateData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()],
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

        $sector = $this->service->changeStatus(
            $id,
            (string) $data['status'],
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()],
        ]);
    }

    public function connections(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $sector = $this->service->getWithConnectionCount($id);

        return Response::json([
            'data' => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()],
        ]);
    }

    public function coverage(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $data = $this->service->getCoverageData();

        return Response::json([
            'data' => $data,
        ]);
    }

    public function byTower(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $sectors = $this->service->getByTower($id);

        return Response::json([
            'data' => array_map(fn($sector) => ['type' => 'sectors', 'id' => (string) $sector->id, 'attributes' => $sector->toArray()], $sectors),
        ]);
    }

    private function parseFilters(Request $request): SectorListFilters
    {
        $query = $request->getQueryParams();

        return new SectorListFilters(
            search: $query['search'] ?? null,
            status: $query['status'] ?? null,
            towerId: isset($query['tower_id']) ? (int) $query['tower_id'] : null,
            deviceId: isset($query['device_id']) ? (int) $query['device_id'] : null,
            frequencyMhz: isset($query['frequency_mhz']) ? (int) $query['frequency_mhz'] : null,
            page: isset($query['page']) ? max(1, (int) $query['page']) : 1,
            perPage: isset($query['per_page']) ? min(max(1, (int) $query['per_page']), 100) : 15,
            sort: $query['sort'] ?? '-created_at',
        );
    }

    private function parseCreateData(array $data): CreateSectorData
    {
        return new CreateSectorData(
            towerId: (int) ($data['tower_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            azimuth: (int) ($data['azimuth'] ?? 0),
            beamwidth: isset($data['beamwidth']) ? (int) $data['beamwidth'] : null,
            frequencyMhz: (int) ($data['frequency_mhz'] ?? 0),
            channelWidthMhz: isset($data['channel_width_mhz']) ? (int) $data['channel_width_mhz'] : null,
            ssid: $data['ssid'] ?? null,
            eirpDbm: isset($data['eirp_dbm']) ? (int) $data['eirp_dbm'] : null,
            deviceId: isset($data['device_id']) ? (int) $data['device_id'] : null,
            capacityMbps: isset($data['capacity_mbps']) ? (int) $data['capacity_mbps'] : null,
            maxSubscribers: isset($data['max_subscribers']) ? (int) $data['max_subscribers'] : null,
            status: (string) ($data['status'] ?? 'planning'),
            notes: $data['notes'] ?? null,
        );
    }

    private function parseUpdateData(array $data): UpdateSectorData
    {
        return new UpdateSectorData(
            towerId: isset($data['tower_id']) ? (int) $data['tower_id'] : null,
            name: $data['name'] ?? null,
            azimuth: isset($data['azimuth']) ? (int) $data['azimuth'] : null,
            beamwidth: isset($data['beamwidth']) ? (int) $data['beamwidth'] : null,
            frequencyMhz: isset($data['frequency_mhz']) ? (int) $data['frequency_mhz'] : null,
            channelWidthMhz: isset($data['channel_width_mhz']) ? (int) $data['channel_width_mhz'] : null,
            ssid: $data['ssid'] ?? null,
            eirpDbm: isset($data['eirp_dbm']) ? (int) $data['eirp_dbm'] : null,
            deviceId: isset($data['device_id']) ? (int) $data['device_id'] : null,
            capacityMbps: isset($data['capacity_mbps']) ? (int) $data['capacity_mbps'] : null,
            maxSubscribers: isset($data['max_subscribers']) ? (int) $data['max_subscribers'] : null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
