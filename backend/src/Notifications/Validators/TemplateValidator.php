<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Validators;

use SkyFi\Notifications\DTOs\CreateTemplateData;
use SkyFi\Notifications\Services\NotificationCatalog;
use SkyFi\Shared\Exceptions\ValidationException;

final class TemplateValidator
{
    public function __construct(private readonly NotificationCatalog $catalog) {}

    public function create(CreateTemplateData $data): void
    {
        $errors = [];
        if ($data->code === '' || strlen($data->code) > 100) {
            $errors[] = ['code' => 'invalid_code', 'detail' => 'Template code is required (max 100 chars).', 'source' => ['pointer' => '/data/attributes/code']];
        }
        if ($data->name === '' || strlen($data->name) > 180) {
            $errors[] = ['code' => 'invalid_name', 'detail' => 'Template name is required (max 180 chars).', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if (!in_array($data->category, $this->catalog->categories(), true)) {
            $errors[] = ['code' => 'invalid_category', 'detail' => 'Invalid template category.', 'source' => ['pointer' => '/data/attributes/category']];
        }
        if (!in_array($data->channel, $this->catalog->channels(), true)) {
            $errors[] = ['code' => 'invalid_channel', 'detail' => 'Invalid template channel.', 'source' => ['pointer' => '/data/attributes/channel']];
        }
        if (trim($data->bodyTemplate) === '') {
            $errors[] = ['code' => 'body_required', 'detail' => 'Body template is required.', 'source' => ['pointer' => '/data/attributes/body_template']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): void
    {
        $errors = [];
        if (isset($values['code']) && (trim((string) $values['code']) === '' || strlen((string) $values['code']) > 100)) {
            $errors[] = ['code' => 'invalid_code', 'detail' => 'Template code is invalid.', 'source' => ['pointer' => '/data/attributes/code']];
        }
        if (isset($values['name']) && (trim((string) $values['name']) === '' || strlen((string) $values['name']) > 180)) {
            $errors[] = ['code' => 'invalid_name', 'detail' => 'Template name is invalid.', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if (isset($values['category']) && !in_array((string) $values['category'], $this->catalog->categories(), true)) {
            $errors[] = ['code' => 'invalid_category', 'detail' => 'Invalid template category.', 'source' => ['pointer' => '/data/attributes/category']];
        }
        if (isset($values['channel']) && !in_array((string) $values['channel'], $this->catalog->channels(), true)) {
            $errors[] = ['code' => 'invalid_channel', 'detail' => 'Invalid template channel.', 'source' => ['pointer' => '/data/attributes/channel']];
        }
        if (isset($values['body_template']) && trim((string) $values['body_template']) === '') {
            $errors[] = ['code' => 'body_required', 'detail' => 'Body template is required.', 'source' => ['pointer' => '/data/attributes/body_template']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
