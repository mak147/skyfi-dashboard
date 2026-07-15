<?php

declare(strict_types=1);

namespace SkyFi\Integration\Validators;

use SkyFi\Integration\DTOs\CreateClientApplicationData;
use SkyFi\Shared\Exceptions\ValidationException;

final class ClientApplicationValidator
{
    public function create(CreateClientApplicationData $data): void
    {
        $errors = [];
        if ($data->name === '') {
            $errors[] = ['code' => 'name_required', 'detail' => 'Application name is required.', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if ($data->rateLimitPerMinute < 1) {
            $errors[] = ['code' => 'invalid_rate_limit', 'detail' => 'Rate limit must be at least 1 per minute.', 'source' => ['pointer' => '/data/attributes/rate_limit_per_minute']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
