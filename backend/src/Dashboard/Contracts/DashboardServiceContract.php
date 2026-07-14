<?php

declare(strict_types=1);

namespace SkyFi\Dashboard\Contracts;

use SkyFi\Dashboard\Data\DashboardPayload;

interface DashboardServiceContract
{
    /**
     * Builds the authenticated user's role-aware dashboard payload.
     *
     * @param array<int, string> $roles Role names from the validated JWT claims.
     */
    public function dashboardForRoles(array $roles): DashboardPayload;
}
