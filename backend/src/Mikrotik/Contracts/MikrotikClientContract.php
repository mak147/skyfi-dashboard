<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DomainModels\RouterConnectionData;
use SkyFi\Mikrotik\DomainModels\RouterDiscovery;
use SkyFi\Mikrotik\DomainModels\RouterHealthSnapshot;

interface MikrotikClientContract
{
    /** @return array<string, mixed> */
    public function testConnection(RouterConnectionData $connection): array;

    public function discover(int $routerId, RouterConnectionData $connection): RouterDiscovery;

    public function checkHealth(int $routerId, RouterConnectionData $connection): RouterHealthSnapshot;
}
