<?php

declare(strict_types=1);

namespace SkyFi\Mikrotik\Exceptions;

use SkyFi\Shared\Exceptions\AppException;

final class MikrotikCommandException extends AppException
{
    public function __construct(string $message = 'The router rejected a monitoring command.')
    {
        parent::__construct($message, 503, 'mikrotik_command_failed');
    }
}
