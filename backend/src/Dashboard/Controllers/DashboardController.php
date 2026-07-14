<?php

declare(strict_types=1);

namespace SkyFi\Dashboard\Controllers;

use SkyFi\Dashboard\Contracts\DashboardServiceContract;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class DashboardController
{
    public function __construct(private readonly DashboardServiceContract $dashboard)
    {
    }

    public function show(Request $request): Response
    {
        $claims = $request->attributes()['claims'] ?? [];
        $roles = is_array($claims) && isset($claims['rol']) && is_array($claims['rol'])
            ? array_map(static fn (mixed $role): string => (string) $role, $claims['rol'])
            : [];

        $payload = $this->dashboard->dashboardForRoles($roles);

        return ApiResponse::resource('dashboard', $payload->id(), $payload->toArray());
    }
}
