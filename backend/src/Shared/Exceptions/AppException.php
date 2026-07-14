<?php

declare(strict_types=1);

namespace SkyFi\Shared\Exceptions;

use Exception;

abstract class AppException extends Exception
{
    /**
     * @param string $message Safe message intended for the API consumer.
     * @param int $statusCode HTTP status code.
     * @param string $errorCode Stable machine-readable error code.
     * @param array<int, array<string, mixed>> $details Structured error details.
     */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly string $errorCode,
        private readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    /** @return int HTTP status code. */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** @return string Stable machine-readable error code. */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /** @return array<int, array<string, mixed>> Structured error details. */
    public function details(): array
    {
        return $this->details;
    }
}
