<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Contracts;

use SkyFi\Shared\Auth\Models\User;

interface JwtServiceContract
{
    /** Creates a short-lived signed access token. */
    public function issue(User $user): string;

    /** @return array<string, mixed> Validated claims. */
    public function validate(string $token): array;
}
