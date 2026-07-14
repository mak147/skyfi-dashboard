<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

interface CredentialCipherContract
{
    public function encrypt(string $plaintext): string;

    public function decrypt(string $ciphertext): string;
}
