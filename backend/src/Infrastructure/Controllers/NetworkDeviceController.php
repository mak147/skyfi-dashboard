<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Controllers;

use SkyFi\Infrastructure\Contracts\NetworkDeviceServiceContract;
use SkyFi\Infrastructure\Data\CreateNetworkDeviceData;
use SkyFi\Infrastructure\Data\NetworkDeviceListFilters;
use SkyFi\Infrastructure\Data\UpdateNetworkDeviceData;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;
use SkyFi\Shared\Exceptions\ValidationException;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;

final class NetworkDeviceController
{
    public function __construct(
        private readonly NetworkDeviceServiceContract $service,
        private readonly RequirePermissionMiddleware $permissionMiddleware,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $filters = $this->parseFilters($request);
        $result = $this->service->list($filters);

        return Response::json([
            'data' => array_map(fn($device) => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()], $result['items']),
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

        $device = $this->service->create(
            $createData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()],
        ], 201);
    }

    public function show(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $device = $this->service->get($id);

        return Response::json([
            'data' => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()],
        ]);
    }

    public function update(Request $request): Response
    {
        $claims = $request->getAttribute('claims');
        $this->permissionMiddleware->authorize($claims['sub'], 'infrastructure.update');

        $id = (int) $request->getAttribute('route_params')['id'];
        $data = $request->getParsedBody();
        $updateData = $this->parseUpdateData($data);

        $device = $this->service->update(
            $id,
            $updateData,
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()],
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

        $device = $this->service->changeStatus(
            $id,
            (string) $data['status'],
            (int) $claims['sub'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
            $request->getServerParams()['HTTP_USER_AGENT'] ?? null,
        );

        return Response::json([
            'data' => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()],
        ]);
    }

    public function byType(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $type = $request->getAttribute('route_params')['type'];
        $devices = $this->service->getByType($type);

        return Response::json([
            'data' => array_map(fn($device) => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()], $devices),
        ]);
    }

    public function byPopSite(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $devices = $this->service->getByPopSite($id);

        return Response::json([
            'data' => array_map(fn($device) => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()], $devices),
        ]);
    }

    public function byTower(Request $request): Response
    {
        $this->permissionMiddleware->authorize($request->getAttribute('claims')['sub'], 'infrastructure.view');

        $id = (int) $request->getAttribute('route_params')['id'];
        $devices = $this->service->getByTower($id);

        return Response::json([
            'data' => array_map(fn($device) => ['type' => 'network-devices', 'id' => (string) $device->id, 'attributes' => $device->toArray()], $devices),
        ]);
    }

    private function parseFilters(Request $request): NetworkDeviceListFilters
    {
        $query = $request->getQueryParams();

        return new NetworkDeviceListFilters(
            search: $query['search'] ?? null,
            status: $query['status'] ?? null,
            deviceType: $query['device_type'] ?? null,
            popSiteId: isset($query['pop_site_id']) ? (int) $query['pop_site_id'] : null,
            towerId: isset($query['tower_id']) ? (int) $query['tower_id'] : null,
            mikrotikRouterId: isset($query['mikrotik_router_id']) ? (int) $query['mikrotik_router_id'] : null,
            page: isset($query['page']) ? max(1, (int) $query['page']) : 1,
            perPage: isset($query['per_page']) ? min(max(1, (int) $query['per_page']), 100) : 15,
            sort: $query['sort'] ?? '-created_at',
        );
    }

    private function parseCreateData(array $data): CreateNetworkDeviceData
    {
        return new CreateNetworkDeviceData(
            popSiteId: isset($data['pop_site_id']) ? (int) $data['pop_site_id'] : null,
            towerId: isset($data['tower_id']) ? (int) $data['tower_id'] : null,
            name: (string) ($data['name'] ?? ''),
            deviceType: (string) ($data['device_type'] ?? 'router'),
            vendor: $data['vendor'] ?? null,
            model: $data['model'] ?? null,
            serialNumber: $data['serial_number'] ?? null,
            macAddress: $data['mac_address'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            firmwareVersion: $data['firmware_version'] ?? null,
            locationDescription: $data['location_description'] ?? null,
            managementVlan: isset($data['management_vlan']) ? (int) $data['management_vlan'] : null,
            managementUsername: $data['management_username'] ?? null,
            managementPassword: $data['management_password'] ?? null,
            status: (string) ($data['status'] ?? 'inventory'),
            notes: $data['notes'] ?? null,
            mikrotikRouterId: isset($data['mikrotik_router_id']) ? (int) $data['mikrotik_router_id'] : null,
        );
    }

    private function parseUpdateData(array $data): UpdateNetworkDeviceData
    {
        return new UpdateNetworkDeviceData(
            popSiteId: isset($data['pop_site_id']) ? (int) $data['pop_site_id'] : null,
            towerId: isset($data['tower_id']) ? (int) $data['tower_id'] : null,
            name: $data['name'] ?? null,
            deviceType: $data['device_type'] ?? null,
            vendor: $data['vendor'] ?? null,
            model: $data['model'] ?? null,
            serialNumber: $data['serial_number'] ?? null,
            macAddress: $data['mac_address'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            firmwareVersion: $data['firmware_version'] ?? null,
            locationDescription: $data['location_description'] ?? null,
            managementVlan: isset($data['management_vlan']) ? (int) $data['management_vlan'] : null,
            managementUsername: $data['management_username'] ?? null,
            managementPassword: $data['management_password'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
            mikrotikRouterId: isset($data['mikrotik_router_id']) ? (int) $data['mikrotik_router_id'] : null,
        );
    }
}
