<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Controllers;

use SkyFi\Rbac\Middleware\RequirePermissionMiddleware;
use SkyFi\Rbac\Services\RbacService;
use SkyFi\Shared\Http\ApiResponse;
use SkyFi\Shared\Http\Request;
use SkyFi\Shared\Http\Response;

final class RbacController
{
    public function __construct(
        private readonly RbacService $service,
        private readonly RequirePermissionMiddleware $authorizer,
    ) {
    }

    private function getUserIdFromRequest(Request $request): int
    {
        $claims = $request->attributes()['claims'] ?? null;
        return $claims && isset($claims['sub']) ? (int) $claims['sub'] : 0;
    }

    public function getAllRoles(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, '*'); // Or specifically 'view:role' but '*' for Super Admin which usually manages roles. Let's use 'manage:roles' if we want. Wait, I will use 'manage:roles'.
        
        $roles = array_map(fn($r) => $r->toArray(), $this->service->getAllRoles());
        return new Response(200, ['data' => $roles]);
    }

    public function getRole(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'manage:roles');
        
        $params = $request->attributes()['route_params'] ?? [];
        $role = $this->service->getRole((int) ($params['id'] ?? 0))->toArray();
        return new Response(200, ['data' => $role]);
    }

    public function createRole(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'manage:roles');
        
        $body = $request->body();
        $role = $this->service->createRole(
            $body['name'] ?? '',
            $body['description'] ?? '',
            $body['permissions'] ?? [],
            $userId,
            $request->ipAddress(),
            $request->userAgent()
        );
        
        return new Response(201, ['data' => $role->toArray()]);
    }

    public function updateRole(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'manage:roles');
        
        $params = $request->attributes()['route_params'] ?? [];
        $body = $request->body();
        $role = $this->service->updateRole(
            (int) ($params['id'] ?? 0),
            $body['name'] ?? '',
            $body['description'] ?? '',
            $body['permissions'] ?? [],
            $userId,
            $request->ipAddress(),
            $request->userAgent()
        );
        
        return new Response(200, ['data' => $role->toArray()]);
    }

    public function deleteRole(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'manage:roles');
        
        $params = $request->attributes()['route_params'] ?? [];
        $this->service->deleteRole((int) ($params['id'] ?? 0), $userId, $request->ipAddress(), $request->userAgent());
        return new Response(204);
    }

    public function getAllPermissions(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'manage:roles');
        
        $permissions = array_map(fn($p) => $p->toArray(), $this->service->getAllPermissions());
        return new Response(200, ['data' => $permissions]);
    }

    public function getUserRoles(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'manage:roles');
        
        $params = $request->attributes()['route_params'] ?? [];
        $roles = array_map(fn($r) => $r->toArray(), $this->service->getUserRoles((int) ($params['id'] ?? 0)));
        return new Response(200, ['data' => $roles]);
    }

    public function syncUserRoles(Request $request): Response
    {
        $userId = $this->getUserIdFromRequest($request);
        $this->authorizer->authorize($userId, 'manage:roles');
        
        $params = $request->attributes()['route_params'] ?? [];
        $body = $request->body();
        $roles = array_map(
            fn($r) => $r->toArray(), 
            $this->service->syncUserRoles(
                (int) ($params['id'] ?? 0),
                $body['roles'] ?? [],
                $userId,
                $request->ipAddress(),
                $request->userAgent()
            )
        );
        
        return new Response(200, ['data' => $roles]);
    }
}
