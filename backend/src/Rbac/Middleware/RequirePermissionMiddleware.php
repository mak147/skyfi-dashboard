<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Middleware;

use SkyFi\Rbac\Contracts\RbacRepositoryContract;
use SkyFi\Shared\Exceptions\AuthorizationException;
use SkyFi\Shared\Http\Request;

final class RequirePermissionMiddleware
{
    public function __construct(private readonly RbacRepositoryContract $repository)
    {
    }

    public function authorize(int $userId, string $requiredPermission): void
    {
        // For simplicity, we get all roles for the user, then check if they have the required permission.
        // A real system might cache this.
        $roles = $this->repository->getUserRoles($userId);
        
        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->name === $requiredPermission || $permission->name === '*') {
                    return;
                }
            }
        }
        
        throw new AuthorizationException('You do not have permission to perform this action.');
    }
}
