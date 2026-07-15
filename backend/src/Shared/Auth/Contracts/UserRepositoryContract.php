<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Contracts;

use SkyFi\Shared\Auth\Models\User;

interface UserRepositoryContract
{
    /** Finds an active user by normalized email. */
    public function findByEmail(string $email): ?User;

    /** Finds an active user by ID. */
    public function findById(int $id): ?User;

    /** Returns the linked customer_id for a user, if any. */
    public function findCustomerIdByUserId(int $id): ?int;
}
