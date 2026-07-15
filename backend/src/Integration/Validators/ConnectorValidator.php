<?php

declare(strict_types=1);

namespace SkyFi\Integration\Validators;

use SkyFi\Integration\DTOs\UpdateConnectorData;
use SkyFi\Shared\Exceptions\ValidationException;

final class ConnectorValidator
{
    public function update(UpdateConnectorData $data): void
    {
        $errors = [];
        if ($data->name !== null && $data->name === '') {
            $errors[] = ['code' => 'name_required', 'detail' => 'Connector name cannot be empty.', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if ($data->config !== null && !is_array($data->config)) {
            $errors[] = ['code' => 'invalid_config', 'detail' => 'Connector config must be a valid object.', 'source' => ['pointer' => '/data/attributes/config']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
