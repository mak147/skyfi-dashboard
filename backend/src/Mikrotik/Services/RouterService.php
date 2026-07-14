<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Services;

use SkyFi\Mikrotik\Contracts\CredentialCipherContract;
use SkyFi\Mikrotik\Contracts\RouterGroupRepositoryContract;
use SkyFi\Mikrotik\Contracts\RouterRepositoryContract;
use SkyFi\Mikrotik\Contracts\RouterServiceContract;
use SkyFi\Mikrotik\Contracts\RouterTagRepositoryContract;
use SkyFi\Mikrotik\DTOs\CreateRouterData;
use SkyFi\Mikrotik\DTOs\RouterListFilters;
use SkyFi\Mikrotik\DTOs\UpdateRouterData;
use SkyFi\Mikrotik\DomainModels\Router;
use SkyFi\Mikrotik\DomainModels\RouterConnectionData;
use SkyFi\Mikrotik\Validators\RouterValidator;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class RouterService implements RouterServiceContract
{
    public function __construct(
        private readonly RouterRepositoryContract $routers,
        private readonly RouterGroupRepositoryContract $groups,
        private readonly RouterTagRepositoryContract $tags,
        private readonly CredentialCipherContract $cipher,
        private readonly RouterValidator $validator,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function list(RouterListFilters $filters): array
    {
        return $this->routers->list($filters);
    }

    public function get(int $id): Router
    {
        return $this->routers->find($id) ?? throw new NotFoundException('Router not found.');
    }

    public function create(CreateRouterData $data, int $actorId, ?string $ip, ?string $userAgent): Router
    {
        $this->validator->validateCreate($data);
        $this->assertNameAvailable($data->name);
        $this->assertRelations($data->routerGroupId, $data->tagIds);

        $router = $this->routers->create([
            'router_group_id' => $data->routerGroupId,
            'name' => $data->name,
            'host' => $data->host,
            'api_port' => $data->apiPort,
            'api_username' => $data->apiUsername,
            'api_password_encrypted' => $this->cipher->encrypt($data->apiPassword),
            'location' => $data->location,
            'site' => $data->site,
            'notes' => $data->notes,
            'is_enabled' => $data->isEnabled ? 1 : 0,
            'last_connection_status' => $data->isEnabled ? 'unknown' : 'disabled',
            'created_by' => $actorId,
        ]);
        $this->routers->syncTags($router->id(), $data->tagIds);
        $router = $this->get($router->id());
        $this->auditLogger->log($actorId, 'create', 'mikrotik_router', $router->id(), null, $router->toArray(), $ip, $userAgent);

        return $router;
    }

    public function update(int $id, UpdateRouterData $data, int $actorId, ?string $ip, ?string $userAgent): Router
    {
        $existing = $this->get($id);
        $this->validator->validateUpdate($data);
        $this->assertNameAvailable($data->name, $id);
        $this->assertRelations($data->routerGroupId, $data->tagIds);

        $attributes = [
            'router_group_id' => $data->routerGroupId,
            'name' => $data->name,
            'host' => $data->host,
            'api_port' => $data->apiPort,
            'api_username' => $data->apiUsername,
            'location' => $data->location,
            'site' => $data->site,
            'notes' => $data->notes,
            'updated_by' => $actorId,
        ];
        if ($data->apiPassword !== null) {
            $attributes['api_password_encrypted'] = $this->cipher->encrypt($data->apiPassword);
        }
        $router = $this->routers->update($id, $attributes);
        $this->routers->syncTags($id, $data->tagIds);
        $router = $this->get($id);
        $this->auditLogger->log($actorId, 'update', 'mikrotik_router', $id, $existing->toArray(), $router->toArray(), $ip, $userAgent);

        return $router;
    }

    public function delete(int $id, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $router = $this->get($id);
        $this->routers->softDelete($id);
        $this->auditLogger->log($actorId, 'delete', 'mikrotik_router', $id, $router->toArray(), null, $ip, $userAgent);
    }

    public function setEnabled(int $id, bool $isEnabled, int $actorId, ?string $ip, ?string $userAgent): Router
    {
        $existing = $this->get($id);
        $router = $this->routers->update($id, [
            'is_enabled' => $isEnabled ? 1 : 0,
            'last_connection_status' => $isEnabled ? 'unknown' : 'disabled',
            'last_connection_error' => null,
            'updated_by' => $actorId,
        ]);
        $this->auditLogger->log(
            $actorId,
            $isEnabled ? 'enable' : 'disable',
            'mikrotik_router',
            $id,
            ['is_enabled' => $existing->isEnabled()],
            ['is_enabled' => $router->isEnabled()],
            $ip,
            $userAgent,
        );

        return $router;
    }

    public function syncTags(int $id, array $tagIds, int $actorId, ?string $ip, ?string $userAgent): Router
    {
        $existing = $this->get($id);
        $this->assertRelations(null, $tagIds);
        $this->routers->syncTags($id, $tagIds);
        $router = $this->get($id);
        $this->auditLogger->log($actorId, 'sync_tags', 'mikrotik_router', $id, ['tags' => $existing->toArray()['tags']], ['tags' => $router->toArray()['tags']], $ip, $userAgent);

        return $router;
    }

    public function connectionData(int $id): RouterConnectionData
    {
        $router = $this->get($id);
        if (!$router->isEnabled()) {
            throw new ValidationException([[
                'code' => 'router_disabled',
                'detail' => 'Enable the router integration before testing, discovery, or health checks.',
                'source' => ['pointer' => '/data/attributes/is_enabled'],
            ]]);
        }

        return new RouterConnectionData(
            host: $router->host(),
            apiPort: $router->apiPort(),
            username: $router->apiUsername(),
            password: $this->cipher->decrypt($router->encryptedPassword()),
        );
    }

    private function assertNameAvailable(string $name, ?int $excludeId = null): void
    {
        if ($this->routers->existsByName($name, $excludeId)) {
            throw new ValidationException([[
                'code' => 'unique',
                'detail' => 'A router with this name already exists.',
                'source' => ['pointer' => '/data/attributes/name'],
            ]]);
        }
    }

    /** @param array<int, int> $tagIds */
    private function assertRelations(?int $groupId, array $tagIds): void
    {
        if ($groupId !== null && $this->groups->find($groupId) === null) {
            throw new ValidationException([[
                'code' => 'exists',
                'detail' => 'The selected router group does not exist.',
                'source' => ['pointer' => '/data/attributes/router_group_id'],
            ]]);
        }
        $existingTagIds = $this->tags->existingIds($tagIds);
        if (count($existingTagIds) !== count($tagIds)) {
            throw new ValidationException([[
                'code' => 'exists',
                'detail' => 'One or more selected router tags do not exist.',
                'source' => ['pointer' => '/data/attributes/tag_ids'],
            ]]);
        }
    }
}
