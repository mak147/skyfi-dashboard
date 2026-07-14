<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\DomainModels;

/** Short-lived, in-memory data required to authenticate to a router. */
final class RouterConnectionData
{
    public function __construct(
        public readonly string $host,
        public readonly int $apiPort,
        public readonly string $username,
        public readonly string $password,
    ) {
    }
}
