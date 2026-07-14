<?php

declare(strict_types=1);

namespace SkyFi\Shared\Exceptions;

final class ValidationException extends AppException
{
    /**
     * @param array<int, array<string, mixed>> $details Validation errors.
     */
    public function __construct(array $details)
    {
        parent::__construct(
            'The given data was invalid.',
            422,
            'validation_failed',
            $details,
        );
    }
}
