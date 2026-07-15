<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Models;

final class User
{
    /**
     * @param array<int, string> $roles
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $customerId,
        public readonly string $passwordHash,
        public readonly array $roles = [],
    ) {
    }

    /** @return array<string, mixed> Safe representation for API responses and JWT claims. */
    public function publicAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles,
        ];
    }
}
