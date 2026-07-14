<?php

declare(strict_types=1);

namespace SkyFi\Rbac\Repositories;

use PDO;
use SkyFi\Rbac\Contracts\RbacRepositoryContract;
use SkyFi\Rbac\Models\Permission;
use SkyFi\Rbac\Models\Role;

final class PdoRbacRepository implements RbacRepositoryContract
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getAllRoles(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM roles ORDER BY name ASC');
        $rows = $stmt->fetchAll();
        
        $roles = [];
        foreach ($rows as $row) {
            $roles[] = new Role((int)$row['id'], $row['name'], $row['description'], $this->getRolePermissions((int)$row['id']));
        }
        return $roles;
    }

    public function getRoleById(int $id): ?Role
    {
        $stmt = $this->pdo->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }

        return new Role((int)$row['id'], $row['name'], $row['description'], $this->getRolePermissions((int)$row['id']));
    }

    public function createRole(string $name, string $description): Role
    {
        $stmt = $this->pdo->prepare('INSERT INTO roles (name, description) VALUES (?, ?)');
        $stmt->execute([$name, $description]);
        $id = (int)$this->pdo->lastInsertId();
        
        return new Role($id, $name, $description);
    }

    public function updateRole(int $id, string $name, string $description): Role
    {
        $stmt = $this->pdo->prepare('UPDATE roles SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([$name, $description, $id]);
        
        return new Role($id, $name, $description, $this->getRolePermissions($id));
    }

    public function deleteRole(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM roles WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getAllPermissions(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM permissions ORDER BY name ASC');
        $rows = $stmt->fetchAll();
        
        $permissions = [];
        foreach ($rows as $row) {
            $permissions[] = new Permission((int)$row['id'], $row['name'], $row['description']);
        }
        return $permissions;
    }

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare('DELETE FROM permission_role WHERE role_id = ?');
            $stmt->execute([$roleId]);
            
            if (!empty($permissionIds)) {
                $insertStmt = $this->pdo->prepare('INSERT INTO permission_role (role_id, permission_id) VALUES (?, ?)');
                foreach ($permissionIds as $pId) {
                    $insertStmt->execute([$roleId, $pId]);
                }
            }
            
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getUserRoles(int $userId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT r.* FROM roles r
            JOIN role_user ru ON ru.role_id = r.id
            WHERE ru.user_id = ?
            ORDER BY r.name ASC
        ');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        
        $roles = [];
        foreach ($rows as $row) {
            $roles[] = new Role((int)$row['id'], $row['name'], $row['description'], $this->getRolePermissions((int)$row['id']));
        }
        return $roles;
    }

    public function syncUserRoles(int $userId, array $roleIds): void
    {
        try {
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare('DELETE FROM role_user WHERE user_id = ?');
            $stmt->execute([$userId]);
            
            if (!empty($roleIds)) {
                $insertStmt = $this->pdo->prepare('INSERT INTO role_user (user_id, role_id) VALUES (?, ?)');
                foreach ($roleIds as $rId) {
                    $insertStmt->execute([$userId, $rId]);
                }
            }
            
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function userExists(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    private function getRolePermissions(int $roleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT p.* FROM permissions p
            JOIN permission_role pr ON pr.permission_id = p.id
            WHERE pr.role_id = ?
        ');
        $stmt->execute([$roleId]);
        $rows = $stmt->fetchAll();
        
        $permissions = [];
        foreach ($rows as $row) {
            $permissions[] = new Permission((int)$row['id'], $row['name'], $row['description']);
        }
        return $permissions;
    }
}
