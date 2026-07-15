<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Repositories;

use PDO;
use SkyFi\Shared\Auth\Contracts\UserRepositoryContract;
use SkyFi\Shared\Auth\Models\User;

final class PdoUserRepository implements UserRepositoryContract
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, email, customer_id, password, deleted_at FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1',
        );
        $statement->execute(['email' => strtolower($email)]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row, true) : null;
    }

    public function findById(int $id): ?User
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, email, customer_id, password, deleted_at FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row, true) : null;
    }

    public function findCustomerIdByUserId(int $id): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT customer_id FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1',
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false || $row['customer_id'] === null) {
            return null;
        }

        return (int) $row['customer_id'];
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row, bool $withRoles): User
    {
        $roles = [];
        if ($withRoles) {
            $statement = $this->connection->prepare(
                'SELECT r.name FROM roles r INNER JOIN role_user ru ON ru.role_id = r.id WHERE ru.user_id = :user_id ORDER BY r.name',
            );
            $statement->execute(['user_id' => (int) $row['id']]);
            $roles = array_map(
                static fn (array $role): string => (string) $role['name'],
                $statement->fetchAll(PDO::FETCH_ASSOC),
            );
        }

        return new User(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            isset($row['customer_id']) ? (int) $row['customer_id'] : null,
            (string) $row['password'],
            $roles,
        );
    }
}
