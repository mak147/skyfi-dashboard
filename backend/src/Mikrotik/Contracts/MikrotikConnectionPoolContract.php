<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Contracts;

use SkyFi\Mikrotik\DomainModels\RouterConnectionData;

interface MikrotikConnectionPoolContract
{
    /**
     * Executes read-only RouterOS API sentences through one authenticated,
     * bounded-lifetime TLS connection.
     *
     * @param array<int, array<int, string>> $sentences
     * @return array<int, array<int, array<string, string>>>
     */
    public function executeBatch(RouterConnectionData $connection, array $sentences): array;
}
