<?php

declare(strict_types=1);

namespace SkyFi\Integration\Validators;

use SkyFi\Integration\DTOs\CreateWebhookData;
use SkyFi\Integration\DTOs\UpdateWebhookData;
use SkyFi\Shared\Exceptions\ValidationException;

final class WebhookValidator
{
    public function create(CreateWebhookData $data): void
    {
        $errors = [];
        if ($data->name === '') {
            $errors[] = ['code' => 'name_required', 'detail' => 'Webhook name is required.', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if ($data->url === '') {
            $errors[] = ['code' => 'url_required', 'detail' => 'Webhook URL is required.', 'source' => ['pointer' => '/data/attributes/url']];
        }
        if (!filter_var($data->url, FILTER_VALIDATE_URL)) {
            $errors[] = ['code' => 'invalid_url', 'detail' => 'Webhook URL must be a valid URL.', 'source' => ['pointer' => '/data/attributes/url']];
        }
        if (!str_starts_with($data->url, 'https://') && !str_starts_with($data->url, 'http://')) {
            $errors[] = ['code' => 'invalid_url_scheme', 'detail' => 'Webhook URL must use HTTPS (or HTTP for development).', 'source' => ['pointer' => '/data/attributes/url']];
        }
        if ($data->events === []) {
            $errors[] = ['code' => 'events_required', 'detail' => 'At least one event must be subscribed.', 'source' => ['pointer' => '/data/attributes/events']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function update(UpdateWebhookData $data): void
    {
        $errors = [];
        if ($data->name !== null && $data->name === '') {
            $errors[] = ['code' => 'name_required', 'detail' => 'Webhook name cannot be empty.', 'source' => ['pointer' => '/data/attributes/name']];
        }
        if ($data->url !== null && !filter_var($data->url, FILTER_VALIDATE_URL)) {
            $errors[] = ['code' => 'invalid_url', 'detail' => 'Webhook URL must be a valid URL.', 'source' => ['pointer' => '/data/attributes/url']];
        }
        if ($data->events !== null && $data->events === []) {
            $errors[] = ['code' => 'events_required', 'detail' => 'At least one event must be subscribed.', 'source' => ['pointer' => '/data/attributes/events']];
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
