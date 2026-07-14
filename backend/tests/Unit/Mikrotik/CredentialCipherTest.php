<?php

declare(strict_types=1);

namespace SkyFi\Tests\Unit\Mikrotik;

use PHPUnit\Framework\TestCase;
use SkyFi\Mikrotik\Services\CredentialCipher;

final class CredentialCipherTest extends TestCase
{
    public function testItEncryptsAndDecryptsRouterPasswords(): void
    {
        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
            self::markTestSkipped('The sodium extension is unavailable.');
        }
        $cipher = new CredentialCipher(base64_encode(random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES)));

        $encrypted = $cipher->encrypt('router-secret');

        self::assertNotSame('router-secret', $encrypted);
        self::assertSame('router-secret', $cipher->decrypt($encrypted));
    }
}
