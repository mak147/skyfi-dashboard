<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DomainModels\RouterDiscovery;

interface RouterDiscoveryServiceContract
{
    public function discover(int $routerId): RouterDiscovery;

    /** @return array<string, mixed> */
    public function testSavedRouter(int $routerId): array;

    /** @return array<string, mixed> */
    public function testTransient(array $payload): array;
}
