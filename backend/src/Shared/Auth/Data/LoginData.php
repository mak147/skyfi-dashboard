<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Data;

use SkyFi\Shared\Exceptions\ValidationException;

final class LoginData
{
    private function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $rememberMe,
    ) {
    }

    /**
     * Validates untrusted login input at the HTTP boundary.
     *
     * @param array<string, mixed> $input Request body.
     * @throws ValidationException When the request is invalid.
     */
    public static function fromArray(array $input): self
    {
        $errors = [];
        $email = is_string($input['email'] ?? null) ? trim($input['email']) : '';
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';

        if ($email === '') {
            $errors[] = ['detail' => 'Email is required.', 'source' => ['pointer' => '/data/attributes/email']];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['detail' => 'Please enter a valid email address.', 'source' => ['pointer' => '/data/attributes/email']];
        } elseif (strlen($email) > 255) {
            $errors[] = ['detail' => 'Email must be 255 characters or fewer.', 'source' => ['pointer' => '/data/attributes/email']];
        }

        if ($password === '') {
            $errors[] = ['detail' => 'Password is required.', 'source' => ['pointer' => '/data/attributes/password']];
        } elseif (strlen($password) < 8) {
            $errors[] = ['detail' => 'Password must be at least 8 characters long.', 'source' => ['pointer' => '/data/attributes/password']];
        } elseif (strlen($password) > 255) {
            $errors[] = ['detail' => 'Password must be 255 characters or fewer.', 'source' => ['pointer' => '/data/attributes/password']];
        }

        if (isset($input['rememberMe']) && !is_bool($input['rememberMe'])) {
            $errors[] = ['detail' => 'Remember me must be a boolean.', 'source' => ['pointer' => '/data/attributes/rememberMe']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new self($email, $password, $input['rememberMe'] ?? false);
    }
}
