<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Services;

use SkyFi\Rbac\Contracts\AuditLoggerContract;
use SkyFi\Rbac\Contracts\RbacRepositoryContract;
use SkyFi\Rbac\Models\Permission;
use SkyFi\Rbac\Models\Role;
use SkyFi\Shared\Exceptions\NotFoundException;
use SkyFi\Shared\Exceptions\ValidationException;

final class RbacService
{
    public function __construct(
        private readonly RbacRepositoryContract $repository,
        private readonly AuditLoggerContract $auditLogger,
    ) {
    }

    public function getAllRoles(): array
    {
        return $this->repository->getAllRoles();
    }

    public function getRole(int $id): Role
    {
        $role = $this->repository->getRoleById($id);
        if (!$role) {
            throw new NotFoundException('Role not found.');
        }
        return $role;
    }

    public function createRole(string $name, string $description, array $permissionIds, ?int $authUserId, ?string $ip, ?string $ua): Role
    {
        if (trim($name) === '') {
            throw new ValidationException(['name' => 'Role name is required.']);
        }
        
        $role = $this->repository->createRole($name, $description);
        $this->repository->syncRolePermissions($role->id, $permissionIds);
        
        $this->auditLogger->log(
            userId: $authUserId,
            action: 'create',
            entityType: 'role',
            entityId: $role->id,
            oldValues: null,
            newValues: ['name' => $name, 'description' => $description, 'permissions' => $permissionIds],
            ipAddress: $ip,
            userAgent: $ua
        );
        
        return $this->repository->getRoleById($role->id);
    }

    public function updateRole(int $id, string $name, string $description, array $permissionIds, ?int $authUserId, ?string $ip, ?string $ua): Role
    {
        $role = $this->getRole($id);
        
        if (trim($name) === '') {
            throw new ValidationException(['name' => 'Role name is required.']);
        }
        
        $oldValues = ['name' => $role->name, 'description' => $role->description, 'permissions' => array_map(fn($p) => $p->id, $role->permissions)];

        $role = $this->repository->updateRole($id, $name, $description);
        $this->repository->syncRolePermissions($id, $permissionIds);
        
        $newValues = ['name' => $name, 'description' => $description, 'permissions' => $permissionIds];

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update',
            entityType: 'role',
            entityId: $id,
            oldValues: $oldValues,
            newValues: $newValues,
            ipAddress: $ip,
            userAgent: $ua
        );
        
        return $this->repository->getRoleById($id);
    }

    public function deleteRole(int $id, ?int $authUserId, ?string $ip, ?string $ua): void
    {
        $role = $this->getRole($id);
        $oldValues = ['name' => $role->name, 'description' => $role->description];
        
        $this->repository->deleteRole($id);
        
        $this->auditLogger->log(
            userId: $authUserId,
            action: 'delete',
            entityType: 'role',
            entityId: $id,
            oldValues: $oldValues,
            newValues: null,
            ipAddress: $ip,
            userAgent: $ua
        );
    }

    public function getAllPermissions(): array
    {
        return $this->repository->getAllPermissions();
    }

    public function getUserRoles(int $userId): array
    {
        if (!$this->repository->userExists($userId)) {
            throw new NotFoundException('User not found.');
        }
        return $this->repository->getUserRoles($userId);
    }

    public function syncUserRoles(int $targetUserId, array $roleIds, ?int $authUserId, ?string $ip, ?string $ua): array
    {
        if (!$this->repository->userExists($targetUserId)) {
            throw new NotFoundException('User not found.');
        }

        $oldRoles = $this->repository->getUserRoles($targetUserId);
        $oldRoleIds = array_map(fn($r) => $r->id, $oldRoles);

        $this->repository->syncUserRoles($targetUserId, $roleIds);

        $this->auditLogger->log(
            userId: $authUserId,
            action: 'update_roles',
            entityType: 'user',
            entityId: $targetUserId,
            oldValues: ['roles' => $oldRoleIds],
            newValues: ['roles' => $roleIds],
            ipAddress: $ip,
            userAgent: $ua
        );

        return $this->repository->getUserRoles($targetUserId);
    }
}
