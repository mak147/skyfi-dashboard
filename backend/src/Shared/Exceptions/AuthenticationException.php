<?php

declare(strict_types=1);

namespace SkyFi\Shared\Exceptions;

final class AuthenticationException extends AppException
{
    public function __construct(string $message = 'The supplied credentials are invalid.', string $errorCode = 'authentication_failed')
    {
        parent::__construct($message, 401, $errorCode);
    }
}
