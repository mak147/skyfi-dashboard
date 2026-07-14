<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Services;

use SkyFi\Mikrotik\Contracts\MikrotikClientContract;
use SkyFi\Mikrotik\Contracts\RouterHealthRepositoryContract;
use SkyFi\Mikrotik\Contracts\RouterHealthServiceContract;
use SkyFi\Mikrotik\Contracts\RouterRepositoryContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\DomainModels\RouterHealthSnapshot;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;

final class RouterHealthService implements RouterHealthServiceContract
{
    public function __construct(
        private readonly RouterServiceContract $routers,
        private readonly RouterRepositoryContract $routerRepository,
        private readonly RouterHealthRepositoryContract $healthRepository,
        private readonly MikrotikClientContract $client,
    ) {
    }

    public function latest(int $routerId): ?RouterHealthSnapshot
    {
        $this->routers->get($routerId);

        return $this->healthRepository->latestForRouter($routerId);
    }

    public function check(int $routerId): RouterHealthSnapshot
    {
        try {
            $snapshot = $this->client->checkHealth($routerId, $this->routers->connectionData($routerId));
            $saved = $this->healthRepository->create($snapshot);
            $this->routerRepository->updateConnectionStatus($routerId, [
                'last_connection_status' => 'online',
                'last_connection_error' => null,
                'last_connected_at' => gmdate('Y-m-d H:i:s'),
                'last_health_checked_at' => $snapshot->checkedAt,
            ]);

            return $saved;
        } catch (MikrotikConnectionException|MikrotikCommandException $exception) {
            $offline = new RouterHealthSnapshot(
                id: null,
                routerId: $routerId,
                status: 'offline',
                latencyMs: null,
                cpuUsagePercent: null,
                memoryTotalBytes: null,
                memoryFreeBytes: null,
                diskTotalBytes: null,
                diskFreeBytes: null,
                temperatureCelsius: null,
                trafficRxBytes: null,
                trafficTxBytes: null,
                activeUsersCount: null,
                queueCount: null,
                uptime: null,
                errorMessage: substr($exception->getMessage(), 0, 500),
                checkedAt: gmdate('Y-m-d H:i:s'),
            );
            $saved = $this->healthRepository->create($offline);
            $this->routerRepository->updateConnectionStatus($routerId, [
                'last_connection_status' => 'offline',
                'last_connection_error' => $offline->errorMessage,
                'last_health_checked_at' => $offline->checkedAt,
            ]);

            return $saved;
        }
    }
}
