<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Shared\Auth;

use PHPUnit\Framework\TestCase;
use SkyFi\Shared\Auth\Models\User;
use SkyFi\Shared\Auth\Services\JwtTokenService;
use SkyFi\Shared\Exceptions\AuthenticationException;

final class JwtTokenServiceTest extends TestCase
{
    public function testItIssuesAndValidatesAnAccessToken(): void
    {
        $service = new JwtTokenService(
            str_repeat('s', 32),
            'https://api.skyfinetworks.com',
            'https://app.skyfinetworks.com',
        );
        $user = new User(42, 'Ada Lovelace', 'ada@example.com', 'hash', ['Super Administrator']);

        $claims = $service->validate($service->issue($user));

        self::assertSame('42', $claims['sub']);
        self::assertSame(['Super Administrator'], $claims['rol']);
        self::assertSame('https://api.skyfinetworks.com', $claims['iss']);
    }

    public function testItRejectsAChangedToken(): void
    {
        $service = new JwtTokenService(str_repeat('s', 32), 'issuer', 'audience');
        $token = $service->issue(new User(42, 'Ada', 'ada@example.com', 'hash'));
        $parts = explode('.', $token);
        $parts[1] = rtrim(strtr(base64_encode('{"sub":"99"}'), '+/', '-_'), '=');

        $this->expectException(AuthenticationException::class);
        $service->validate(implode('.', $parts));
    }
}
