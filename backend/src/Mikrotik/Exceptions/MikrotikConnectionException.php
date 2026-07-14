<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Exceptions;

use SkyFi\Shared\Exceptions\AppException;

final class MikrotikConnectionException extends AppException
{
    public function __construct(string $message = 'Unable to connect to the router. Verify network access, TLS, and API credentials.')
    {
        parent::__construct($message, 503, 'mikrotik_connection_failed');
    }
}
