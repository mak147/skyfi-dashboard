<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DomainModels\RouterHealthSnapshot;

interface RouterHealthServiceContract
{
    public function latest(int $routerId): ?RouterHealthSnapshot;

    public function check(int $routerId): RouterHealthSnapshot;
}
