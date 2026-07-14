<?php

declare(strict_types=1);

namespace SkyFi\Infrastructure\Contracts;

use SkyFi\Infrastructure\Data\InfrastructureDashboardPayload;

interface InfrastructureDashboardContract
{
    /** Get infrastructure dashboard summary data. */
    public function getSummary(): InfrastructureDashboardPayload;
}