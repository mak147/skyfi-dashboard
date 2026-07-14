<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Contracts;

use SkyFi\Rbac\Models\Role;
use SkyFi\Rbac\Models\Permission;

interface RbacRepositoryContract
{
    /** @return array<int, Role> */
    public function getAllRoles(): array;

    public function getRoleById(int $id): ?Role;

    public function createRole(string $name, string $description): Role;

    public function updateRole(int $id, string $name, string $description): Role;

    public function deleteRole(int $id): void;

    /** @return array<int, Permission> */
    public function getAllPermissions(): array;

    /**
     * @param array<int> $permissionIds
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): void;

    /**
     * @return array<int, Role>
     */
    public function getUserRoles(int $userId): array;

    /**
     * @param array<int> $roleIds
     */
    public function syncUserRoles(int $userId, array $roleIds): void;
    
    /**
     * Check if user exists.
     */
    public function userExists(int $userId): bool;
}
