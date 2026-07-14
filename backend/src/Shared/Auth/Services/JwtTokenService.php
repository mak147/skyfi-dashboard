<?php

declare(strict_types=1);

namespace SkyFi\Shared\Auth\Services;

use SkyFi\Shared\Auth\Contracts\JwtServiceContract;
use SkyFi\Shared\Auth\Models\User;
use SkyFi\Shared\Exceptions\AuthenticationException;

final class JwtTokenService implements JwtServiceContract
{
    /**
     * @param string $secret HS256 signing secret.
     * @param string $issuer JWT issuer.
     * @param string $audience JWT audience.
     * @param int $ttlSeconds Access-token lifetime.
     */
    public function __construct(
        private readonly string $secret,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly int $ttlSeconds = 900,
    ) {
        if (strlen($this->secret) < 32) {
            throw new \InvalidArgumentException('JWT_SECRET must contain at least 32 characters.');
        }
    }

    public function issue(User $user): string
    {
        $now = time();
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttlSeconds,
            'sub' => (string) $user->id,
            'rol' => $user->roles,
        ];
        $encodedHeader = self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signingInput = $encodedHeader . '.' . $encodedPayload;
        $signature = hash_hmac('sha256', $signingInput, $this->secret, true);

        return $signingInput . '.' . self::base64UrlEncode($signature);
    }

    public function validate(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new AuthenticationException('The access token is invalid.', 'token_invalid');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = json_decode(self::base64UrlDecode($encodedHeader), true);
        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        $signature = self::base64UrlDecode($encodedSignature);

        if (!is_array($header) || !is_array($payload) || (($header['alg'] ?? null) !== 'HS256')) {
            throw new AuthenticationException('The access token is invalid.', 'token_invalid');
        }

        $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret, true);
        if (!hash_equals($expectedSignature, $signature)) {
            throw new AuthenticationException('The access token is invalid.', 'token_invalid');
        }

        $now = time();
        if (($payload['iss'] ?? null) !== $this->issuer || ($payload['aud'] ?? null) !== $this->audience) {
            throw new AuthenticationException('The access token is invalid.', 'token_invalid');
        }
        if (!is_int($payload['exp'] ?? null) || $payload['exp'] <= $now) {
            throw new AuthenticationException('The access token has expired.', 'token_expired');
        }
        if (!is_int($payload['nbf'] ?? null) || $payload['nbf'] > $now) {
            throw new AuthenticationException('The access token is not active yet.', 'token_invalid');
        }
        if (!isset($payload['sub']) || !is_string($payload['sub']) || !ctype_digit($payload['sub'])) {
            throw new AuthenticationException('The access token is invalid.', 'token_invalid');
        }

        return $payload;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new AuthenticationException('The access token is invalid.', 'token_invalid');
        }

        return $decoded;
    }
}
