<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Services;

use SkyFi\Mikrotik\Contracts\MikrotikClientContract;
use SkyFi\Mikrotik\Contracts\RouterDiscoveryServiceContract;
use SkyFi\Mikrotik\Contracts\RouterRepositoryContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DTOs\ConnectionTestData;
use SkyFi\Mikrotik\DomainModels\RouterDiscovery;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Mikrotik\Validators\RouterValidator;

final class RouterDiscoveryService implements RouterDiscoveryServiceContract
{
    public function __construct(
        private readonly RouterServiceContract $routers,
        private readonly RouterRepositoryContract $routerRepository,
        private readonly MikrotikClientContract $client,
        private readonly RouterValidator $validator,
    ) {
    }

    public function discover(int $routerId): RouterDiscovery
    {
        try {
            $discovery = $this->client->discover($routerId, $this->routers->connectionData($routerId));
            $this->routerRepository->updateDiscoveryMetadata($routerId, [
                'routeros_version' => $discovery->routerosVersion,
                'model' => $discovery->model,
                'last_connection_status' => 'online',
                'last_connection_error' => null,
                'last_connected_at' => gmdate('Y-m-d H:i:s'),
                'last_discovered_at' => $discovery->discoveredAt,
            ]);

            return $discovery;
        } catch (MikrotikConnectionException|MikrotikCommandException $exception) {
            $this->markOffline($routerId, $exception->getMessage());
            throw $exception;
        }
    }

    public function testSavedRouter(int $routerId): array
    {
        try {
            $result = $this->client->testConnection($this->routers->connectionData($routerId));
            $this->routerRepository->updateConnectionStatus($routerId, [
                'last_connection_status' => 'online',
                'last_connection_error' => null,
                'last_connected_at' => gmdate('Y-m-d H:i:s'),
            ]);

            return $result;
        } catch (MikrotikConnectionException|MikrotikCommandException $exception) {
            $this->markOffline($routerId, $exception->getMessage());
            throw $exception;
        }
    }

    public function testTransient(array $payload): array
    {
        $data = ConnectionTestData::fromArray($payload);
        $this->validator->validateConnectionTest($data);

        return $this->client->testConnection($data->toConnectionData());
    }

    private function markOffline(int $routerId, string $message): void
    {
        $this->routerRepository->updateConnectionStatus($routerId, [
            'last_connection_status' => 'offline',
            'last_connection_error' => substr($message, 0, 500),
        ]);
    }
}
