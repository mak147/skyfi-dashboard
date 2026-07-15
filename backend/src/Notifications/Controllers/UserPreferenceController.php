<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Controllers;

use SkyFi\Notifications\DTOs\UserPreferenceData;
use SkyFi\Notifications\Services\PreferenceService;
use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class UserPreferenceController
{
    public function __construct(
        private readonly PreferenceService $service,
        private readonly RequirePermissionMiddleware $auth,
    ) {}

    public function show(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.preferences');
        $data = $this->service->get($userId);

        return ApiResponse::resource('notification-preferences', (string) $userId, $data);
    }

    public function update(Request $r): Response
    {
        $userId = $this->can($r, 'notifications.preferences');
        $data = $this->service->update($userId, UserPreferenceData::fromArray($r->body()));

        return ApiResponse::resource('notification-preferences', (string) $userId, $data);
    }

    private function can(Request $r, string $permission): int
    {
        $userId = (int) ($r->attributes()['claims']['sub'] ?? 0);
        $this->auth->authorize($userId, $permission);

        return $userId;
    }
}
