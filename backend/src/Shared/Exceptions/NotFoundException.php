<?php

declare(strict_types=1);

namespace SkyFi\Shared\Exceptions;

final class NotFoundException extends AppException
{
    public function __construct(string $message = 'The requested resource was not found.')
    {
        parent::__construct($message, 404, 'not_found');
    }
}
