<?php

declare(strict_types=1);

namespace SkyFi\Hotspot\Services;

use SkyFi\Hotspot\Contracts\HotspotProfileRepositoryContract;
use SkyFi\Hotspot\Contracts\HotspotProfileServiceContract;
use SkyFi\Hotspot\DomainModels\HotspotProfile;
use SkyFi\Hotspot\DTOs\CreateHotspotProfileData;
use SkyFi\Hotspot\DTOs\HotspotProfileListFilters;
use SkyFi\Hotspot\DTOs\UpdateHotspotProfileData;
use SkyFi\Hotspot\Validators\HotspotProfileValidator;
use SkyFi\Mikrotik\Contracts\MikrotikConnectionPoolContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\Exceptions\MikrotikCommandException;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class HotspotProfileService implements HotspotProfileServiceContract
{
    public function __construct(
        private readonly HotspotProfileRepositoryContract $profiles,
        private readonly RouterServiceContract $routerService,
        private readonly MikrotikConnectionPoolContract $pool,
        private readonly HotspotProfileValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(HotspotProfileListFilters $filters): array
    {
        $result = $this->profiles->list($filters);

        $enrichedItems = [];
        foreach ($result['items'] as $profile) {
            $enrichedItems[] = $this->enrichProfileInfo($profile);
        }

        return [
            ...$result,
            'items' => $enrichedItems,
        ];
    }

    public function get(int $id): HotspotProfile
    {
        $profile = $this->profiles->find($id) ?? throw new NotFoundException('Hotspot profile not found.');
        return $this->enrichProfileInfo($profile);
    }

    public function create(CreateHotspotProfileData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotProfile
    {
        $this->validator->validateCreate($data);

        $this->routerService->get($data->routerId);

        $existing = $this->profiles->findByRouterAndName($data->routerId, $data->routerProfileName);
        if ($existing !== null) {
            throw new ValidationException([[
                'code' => 'unique',
                'detail' => 'A hotspot profile with this router profile name already exists on the selected router.',
                'source' => ['pointer' => '/data/attributes/router_profile_name'],
            ]]);
        }

        $insertPayload = [
            'name' => $data->name,
            'router_id' => $data->routerId,
            'router_profile_name' => $data->routerProfileName,
            'rate_limit_up' => $data->rateLimitUp,
            'rate_limit_down' => $data->rateLimitDown,
            'session_timeout' => $data->sessionTimeout,
            'idle_timeout' => $data->idleTimeout,
            'shared_users' => $data->sharedUsers,
            'mac_cookie_timeout' => $data->macCookieTimeout,
            'login_methods' => $data->loginMethods,
            'status' => $data->status,
            'sync_status' => 'synced',
            'notes' => $data->notes,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ];

        $profile = $this->profiles->insert($insertPayload);

        $this->auditLogger->log($actorId, 'create', 'hotspot_profile', $profile->id(), null, $profile->toArray(), $ip, $userAgent);

        return $this->enrichProfileInfo($profile);
    }

    public function update(int $id, UpdateHotspotProfileData $data, int $actorId, ?string $ip, ?string $userAgent): HotspotProfile
    {
        $existing = $this->profiles->find($id) ?? throw new NotFoundException('Hotspot profile not found.');
        $this->validator->validateUpdate($data);

        $updatePayload = ['updated_by' => $actorId];

        if ($data->name !== null) {
            $updatePayload['name'] = $data->name;
        }
        if ($data->routerId !== null) {
            $this->routerService->get($data->routerId);
            $updatePayload['router_id'] = $data->routerId;
        }
        if ($data->routerProfileName !== null) {
            $updatePayload['router_profile_name'] = $data->routerProfileName;
        }
        if (property_exists($data, 'rateLimitUp')) {
            $updatePayload['rate_limit_up'] = $data->rateLimitUp;
        }
        if (property_exists($data, 'rateLimitDown')) {
            $updatePayload['rate_limit_down'] = $data->rateLimitDown;
        }
        if (property_exists($data, 'sessionTimeout')) {
            $updatePayload['session_timeout'] = $data->sessionTimeout;
        }
        if (property_exists($data, 'idleTimeout')) {
            $updatePayload['idle_timeout'] = $data->idleTimeout;
        }
        if ($data->sharedUsers !== null) {
            $updatePayload['shared_users'] = $data->sharedUsers;
        }
        if (property_exists($data, 'macCookieTimeout')) {
            $updatePayload['mac_cookie_timeout'] = $data->macCookieTimeout;
        }
        if ($data->loginMethods !== null) {
            $updatePayload['login_methods'] = $data->loginMethods;
        }
        if ($data->status !== null) {
            $updatePayload['status'] = $data->status;
        }
        if (property_exists($data, 'notes')) {
            $updatePayload['notes'] = $data->notes;
        }

        $updated = $this->profiles->update($id, $updatePayload);

        $this->auditLogger->log($actorId, 'update', 'hotspot_profile', $id, $existing->toArray(), $updated->toArray(), $ip, $userAgent);

        return $this->enrichProfileInfo($updated);
    }

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $existing = $this->profiles->find($id) ?? throw new NotFoundException('Hotspot profile not found.');

        $this->profiles->delete($id);
        $this->auditLogger->log($actorId, 'delete', 'hotspot_profile', $id, $existing->toArray(), null, $ip, $userAgent);
    }

    /** @return array<int, array<string, mixed>> */
    public function fetchRouterProfiles(int $routerId): array
    {
        $connection = $this->routerService->connectionData($routerId);

        $responses = $this->pool->executeBatch($connection, [
            ['/ip/hotspot/user/profile/print']
        ]);
        $rows = $responses[0] ?? [];

        $profiles = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? null;
            if ($name !== null && $name !== '') {
                $profiles[] = [
                    'id' => $row['.id'] ?? null,
                    'name' => $name,
                    'rate_limit' => $row['rate-limit'] ?? null,
                    'session_timeout' => $row['session-timeout'] ?? null,
                    'idle_timeout' => $row['idle-timeout'] ?? null,
                    'shared_users' => $row['shared-users'] ?? null,
                    'mac_cookie_timeout' => $row['mac-cookie-timeout'] ?? null,
                    'login_by' => $row['login-by'] ?? null,
                ];
            }
        }

        return $profiles;
    }

    private function enrichProfileInfo(HotspotProfile $profile): HotspotProfile
    {
        $attributes = $profile->toArray();

        try {
            $router = $this->routerService->get($profile->routerId());
            $attributes['router_name'] = $router->toArray()['name'] ?? 'Unknown Router';
        } catch (\Throwable) {
            $attributes['router_name'] = 'Router #' . $profile->routerId();
        }

        return HotspotProfile::fromRow($attributes);
    }
}
