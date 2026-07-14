<?php

declare(strict_types=1);

namespace SkyFi\Database\Seeders;

use PDO;

final class AuthSeeder
{
    /**
     * Seeds the documented RBAC baseline. A user is created only when both
     * optional credentials are supplied by the environment/CLI.
     *
     * @param string|null $adminEmail Optional initial administrator email.
     * @param string|null $adminPassword Optional initial administrator password.
     */
    public function run(PDO $connection, ?string $adminEmail = null, ?string $adminPassword = null): void
    {
        $connection->beginTransaction();
        try {
            $permissionIds = $this->seedPermissions($connection);
            $roleIds = $this->seedRoles($connection);
            $this->seedRolePermissions($connection, $roleIds, $permissionIds);

            if ($adminEmail !== null && $adminPassword !== null) {
                $userId = $this->seedAdministrator($connection, $adminEmail, $adminPassword);
                $this->assignRole($connection, $userId, $roleIds['Super Administrator']);
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    /** @return array<string, int> */
    private function seedPermissions(PDO $connection): array
    {
        $statement = $connection->prepare(
            'INSERT INTO permissions (name, description, created_at, updated_at) VALUES (:name, :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)\n             ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = CURRENT_TIMESTAMP',
        );
        $ids = [];
        foreach (PermissionCatalog::all() as $name => $description) {
            $statement->execute(['name' => $name, 'description' => $description]);
            $select = $connection->prepare('SELECT id FROM permissions WHERE name = :name');
            $select->execute(['name' => $name]);
            $ids[$name] = (int) $select->fetchColumn();
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function seedRoles(PDO $connection): array
    {
        $roles = [
            'Super Administrator' => 'Unrestricted access; manages system configuration and recovery.',
            'Company Owner' => 'Read-only visibility into company health and reports.',
            'Regional Manager' => 'Manages operations for an assigned geographical region.',
            'Finance Department' => 'Manages billing and financial operations.',
            'Sales Team' => 'Manages leads and new customer acquisition.',
            'Customer Support' => 'Supports customers and manages support cases.',
            'Installation Team / Field Technician' => 'Manages assigned installation and repair work.',
            'Network Engineer' => 'Manages network infrastructure and provisioning.',
            'Inventory Manager' => 'Manages physical inventory and purchasing.',
        ];
        $statement = $connection->prepare(
            'INSERT INTO roles (name, description, created_at, updated_at) VALUES (:name, :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)\n             ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = CURRENT_TIMESTAMP',
        );
        $ids = [];
        foreach ($roles as $name => $description) {
            $statement->execute(['name' => $name, 'description' => $description]);
            $select = $connection->prepare('SELECT id FROM roles WHERE name = :name');
            $select->execute(['name' => $name]);
            $ids[$name] = (int) $select->fetchColumn();
        }

        return $ids;
    }

    /** @param array<string, int> $roleIds @param array<string, int> $permissionIds */
    private function seedRolePermissions(PDO $connection, array $roleIds, array $permissionIds): void
    {
        $rolePermissions = [
            'Super Administrator' => ['*'],
            'Company Owner' => ['view:dashboard:company', 'view:report:financial', 'view:report:subscriber', 'view:report:network', 'view:audit-log', 'customers.view', 'customers.export'],
            'Regional Manager' => ['view:dashboard:regional', 'view:customer', 'view:invoice', 'view:payment', 'view:ticket', 'view:tower', 'view:report:regional', 'update:user:role', 'view:work-order', 'customers.view', 'customers.create', 'customers.update', 'customers.export', 'customers.manage'],
            'Finance Department' => ['manage:invoice', 'manage:payment', 'manage:credit', 'create:refund:small', 'create:refund:large', 'execute:billing-run', 'execute:dunning-process', 'manage:service-plan', 'view:customer', 'view:report:financial', 'customers.view', 'customers.export'],
            'Sales Team' => ['manage:lead', 'execute:service-availability-check', 'create:quote', 'create:customer', 'view:customer:basic', 'view:service-plan', 'customers.view', 'customers.create', 'customers.update'],
            'Customer Support' => ['manage:ticket', 'view:customer', 'update:customer:contact', 'update:customer:notes', 'view:invoice', 'view:payment', 'create:payment:manual', 'view:service', 'view:network-status:customer', 'execute:service:reconnect', 'customers.view', 'customers.update'],
            'Installation Team / Field Technician' => ['view:work-order:own', 'update:work-order:own', 'view:customer:contact_and_address', 'view:inventory:own-vehicle', 'execute:site-survey', 'execute:service:diagnostics', 'customers.view'],
            'Network Engineer' => ['manage:tower', 'manage:mikrotik-router', 'view:ip-address-pool', 'execute:provisioning:manual', 'execute:config-backup', 'view:network-status:global', 'view:customer:network-details', 'view:report:network', 'customers.view', 'customers.manage'],
            'Inventory Manager' => ['manage:inventory-item', 'manage:warehouse', 'execute:stock-transfer', 'manage:vendor', 'manage:purchase-order', 'view:report:inventory', 'customers.view'],
        ];
        $statement = $connection->prepare(
            'INSERT IGNORE INTO permission_role (permission_id, role_id) VALUES (:permission_id, :role_id)',
        );
        foreach ($rolePermissions as $role => $permissions) {
            foreach ($permissions as $permission) {
                $statement->execute([
                    'permission_id' => $permissionIds[$permission],
                    'role_id' => $roleIds[$role],
                ]);
            }
        }
    }

    private function seedAdministrator(PDO $connection, string $email, string $password): int
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new \InvalidArgumentException('The seed administrator email or password is invalid.');
        }

        $statement = $connection->prepare(
            'INSERT INTO users (name, email, password, created_at, updated_at) VALUES (:name, :email, :password, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)\n             ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), deleted_at = NULL, updated_at = CURRENT_TIMESTAMP',
        );
        $statement->execute([
            'name' => 'Super Administrator',
            'email' => strtolower($email),
            'password' => password_hash($password, PASSWORD_ARGON2ID),
        ]);
        $select = $connection->prepare('SELECT id FROM users WHERE email = :email');
        $select->execute(['email' => strtolower($email)]);

        return (int) $select->fetchColumn();
    }

    private function assignRole(PDO $connection, int $userId, int $roleId): void
    {
        $statement = $connection->prepare(
            'INSERT IGNORE INTO role_user (user_id, role_id) VALUES (:user_id, :role_id)',
        );
        $statement->execute(['user_id' => $userId, 'role_id' => $roleId]);
    }
}
