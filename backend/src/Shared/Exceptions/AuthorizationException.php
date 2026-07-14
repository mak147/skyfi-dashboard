<?php

declare(strict_types=1);

namespace SkyFi\Shared\Exceptions;

final class AuthorizationException extends AppException
{
    public function __construct(string $message = 'You are not authorized to perform this action.')
    {
        parent::__construct($message, 403, 'forbidden');
    }
}
