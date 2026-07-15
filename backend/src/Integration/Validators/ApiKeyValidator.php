<?php

declare(strict_types=1);

namespace SkyFi\Integration\Validators;

use SkyFi\Integration\DTOs\CreateApiKeyData;
use SkyFi\Integration\DTOs\UpdateApiKeyData;
use SkyFi\Shared\Exceptions\ValidationException;

final class ApiKeyValidator
{
    public function create(CreateApiKeyData $data): void
    {
        $errors = [];
        if ($data->name === '') {
            $errors[] = ['code' => 'name_required', 'detail' => 'API key name is required.', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if ($data->scopes === []) {
            $errors[] = ['code' => 'scopes_required', 'detail' => 'At least one scope is required.', 'source' => ['pointer' => '/data/attributes/scopes']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function update(UpdateApiKeyData $data): void
    {
        $errors = [];
        if ($data->name !== null && $data->name === '') {
            $errors[] = ['code' => 'name_required', 'detail' => 'API key name cannot be empty.', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if ($data->scopes !== null && $data->scopes === []) {
            $errors[] = ['code' => 'scopes_required', 'detail' => 'At least one scope is required.', 'source' => ['pointer' => '/data/attributes/scopes']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
