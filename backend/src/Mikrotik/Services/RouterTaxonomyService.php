<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Services;

use SkyFi\Mikrotik\Contracts\RouterGroupRepositoryContract;
use SkyFi\Mikrotik\Contracts\RouterTagRepositoryContract;
use SkyFi\Mikrotik\DomainModels\RouterGroup;
use SkyFi\Mikrotik\DomainModels\RouterTag;
use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class RouterTaxonomyService
{
    public function __construct(
        private readonly RouterGroupRepositoryContract $groups,
        private readonly RouterTagRepositoryContract $tags,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    /** @return array<int, RouterGroup> */
    public function groups(): array
    {
        return $this->groups->all();
    }

    public function createGroup(array $payload, int $actorId, ?string $ip, ?string $userAgent): RouterGroup
    {
        [$name, $description] = $this->validatedGroup($payload);
        if ($this->groups->existsByName($name)) {
            throw $this->uniqueError('name', 'A router group with this name already exists.');
        }
        $group = $this->groups->create(['name' => $name, 'description' => $description]);
        $this->auditLogger->log($actorId, 'create', 'mikrotik_router_group', $group->id(), null, $group->toArray(), $ip, $userAgent);

        return $group;
    }

    public function updateGroup(int $id, array $payload, int $actorId, ?string $ip, ?string $userAgent): RouterGroup
    {
        $existing = $this->groups->find($id) ?? throw new NotFoundException('Router group not found.');
        [$name, $description] = $this->validatedGroup($payload);
        if ($this->groups->existsByName($name, $id)) {
            throw $this->uniqueError('name', 'A router group with this name already exists.');
        }
        $group = $this->groups->update($id, ['name' => $name, 'description' => $description]);
        $this->auditLogger->log($actorId, 'update', 'mikrotik_router_group', $id, $existing->toArray(), $group->toArray(), $ip, $userAgent);

        return $group;
    }

    public function deleteGroup(int $id, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $group = $this->groups->find($id) ?? throw new NotFoundException('Router group not found.');
        if ($this->groups->hasActiveRouters($id)) {
            throw new ValidationException([['code' => 'in_use', 'detail' => 'Move or delete active routers before deleting this group.']]);
        }
        $this->groups->delete($id);
        $this->auditLogger->log($actorId, 'delete', 'mikrotik_router_group', $id, $group->toArray(), null, $ip, $userAgent);
    }

    /** @return array<int, RouterTag> */
    public function tags(): array
    {
        return $this->tags->all();
    }

    public function createTag(array $payload, int $actorId, ?string $ip, ?string $userAgent): RouterTag
    {
        [$name, $color] = $this->validatedTag($payload);
        if ($this->tags->existsByName($name)) {
            throw $this->uniqueError('name', 'A router tag with this name already exists.');
        }
        $tag = $this->tags->create(['name' => $name, 'color' => $color]);
        $this->auditLogger->log($actorId, 'create', 'mikrotik_router_tag', $tag->id(), null, $tag->toArray(), $ip, $userAgent);

        return $tag;
    }

    public function updateTag(int $id, array $payload, int $actorId, ?string $ip, ?string $userAgent): RouterTag
    {
        $existing = $this->tags->find($id) ?? throw new NotFoundException('Router tag not found.');
        [$name, $color] = $this->validatedTag($payload);
        if ($this->tags->existsByName($name, $id)) {
            throw $this->uniqueError('name', 'A router tag with this name already exists.');
        }
        $tag = $this->tags->update($id, ['name' => $name, 'color' => $color]);
        $this->auditLogger->log($actorId, 'update', 'mikrotik_router_tag', $id, $existing->toArray(), $tag->toArray(), $ip, $userAgent);

        return $tag;
    }

    public function deleteTag(int $id, int $actorId, ?string $ip, ?string $userAgent): void
    {
        $tag = $this->tags->find($id) ?? throw new NotFoundException('Router tag not found.');
        $this->tags->delete($id);
        $this->auditLogger->log($actorId, 'delete', 'mikrotik_router_tag', $id, $tag->toArray(), null, $ip, $userAgent);
    }

    /** @return array{0: string, 1: string|null} */
    private function validatedGroup(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        if ($name === '' || strlen($name) > 100 || strlen($description) > 500) {
            throw new ValidationException([['code' => 'invalid', 'detail' => 'Provide a group name up to 100 characters and an optional description up to 500 characters.']]);
        }

        return [$name, $description === '' ? null : $description];
    }

    /** @return array{0: string, 1: string|null} */
    private function validatedTag(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $color = trim((string) ($payload['color'] ?? ''));
        if ($name === '' || strlen($name) > 50 || ($color !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color))) {
            throw new ValidationException([['code' => 'invalid', 'detail' => 'Provide a tag name up to 50 characters and an optional hexadecimal colour.']]);
        }

        return [$name, $color === '' ? null : strtoupper($color)];
    }

    private function uniqueError(string $field, string $detail): ValidationException
    {
        return new ValidationException([['code' => 'unique', 'detail' => $detail, 'source' => ['pointer' => '/data/attributes/' . $field]]]);
    }
}
