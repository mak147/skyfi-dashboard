<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Services;

use SkyFi\Mikrotik\Contracts\CredentialCipherContract;
use SkyFi\Mikrotik\Exceptions\MikrotikConnectionException;

/** Encrypts router secrets using authenticated XChaCha20-Poly1305 encryption. */
final class CredentialCipher implements CredentialCipherContract
{
    public function __construct(private readonly string $base64Key)
    {
    }

    public function encrypt(string $plaintext): string
    {
        $key = $this->key();
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $key);

        return base64_encode($nonce . $ciphertext);
    }

    public function decrypt(string $ciphertext): string
    {
        $key = $this->key();
        $payload = base64_decode($ciphertext, true);
        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        if ($payload === false || strlen($payload) <= $nonceLength) {
            throw new MikrotikConnectionException('Stored router credentials are invalid. Re-enter the router API password.');
        }
        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($payload, $nonceLength),
            '',
            substr($payload, 0, $nonceLength),
            $key,
        );
        if ($plaintext === false) {
            throw new MikrotikConnectionException('Stored router credentials could not be decrypted. Re-enter the router API password.');
        }

        return $plaintext;
    }

    private function key(): string
    {
        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
            throw new \RuntimeException('The sodium extension is required to encrypt router credentials.');
        }
        $key = base64_decode($this->base64Key, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new \RuntimeException('MIKROTIK_CREDENTIAL_ENCRYPTION_KEY must be a valid base64-encoded 32-byte key.');
        }

        return $key;
    }
}
