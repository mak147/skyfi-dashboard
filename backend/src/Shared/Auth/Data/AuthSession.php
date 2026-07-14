<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Data;

use SkyFi\Shared\Auth\Models\User;

final class AuthSession
{
    public function __construct(
        public readonly User $user,
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly int $refreshExpiresAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return [
            'accessToken' => $this->accessToken,
            'user' => [
                'id' => (string) $this->user->id,
                ...$this->user->publicAttributes(),
            ],
        ];
    }
}
