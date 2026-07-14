<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DomainModels\RouterHealthSnapshot;

interface RouterHealthRepositoryContract
{
    public function create(RouterHealthSnapshot $snapshot): RouterHealthSnapshot;

    public function latestForRouter(int $routerId): ?RouterHealthSnapshot;
}
