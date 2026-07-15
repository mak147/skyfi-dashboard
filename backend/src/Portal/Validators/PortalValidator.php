<?php

declare(strict_types=1);

namespace SkyFi\Portal\Validators;

use SkyFi\Portal\DTOs\CreateTicketData;
use SkyFi\Portal\DTOs\ReplyTicketData;
use SkyFi\Portal\DTOs\UpdatePreferenceData;
use SkyFi\Portal\DTOs\UpdateProfileData;
use SkyFi\Shared\Exceptions\ValidationException;

final class PortalValidator
{
    public function validateProfile(UpdateProfileData $data): void
    {
        $errors = [];

        if ($data->fullName !== null && trim($data->fullName) === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Full name cannot be empty.', 'source' => ['pointer' => '/data/attributes/full_name']];
        }
        if ($data->email !== null && !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['code' => 'email', 'detail' => 'A valid email address is required.', 'source' => ['pointer' => '/data/attributes/email']];
        }
        if ($data->phone !== null && trim($data->phone) === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Phone number cannot be empty.', 'source' => ['pointer' => '/data/attributes/phone']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateTicket(CreateTicketData $data): void
    {
        $errors = [];

        if ($data->categoryId < 1) {
            $errors[] = ['code' => 'required', 'detail' => 'A category is required.', 'source' => ['pointer' => '/data/attributes/category_id']];
        }
        if ($data->subject === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Subject is required.', 'source' => ['pointer' => '/data/attributes/subject']];
        }
        if ($data->description === '') {
            $errors[] = ['code' => 'required', 'detail' => 'Description is required.', 'source' => ['pointer' => '/data/attributes/description']];
        }
        if (!in_array($data->priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $errors[] = ['code' => 'invalid', 'detail' => 'Priority is invalid.', 'source' => ['pointer' => '/data/attributes/priority']];
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateReply(ReplyTicketData $data): void
    {
        if ($data->body === '') {
            throw new ValidationException([
                ['code' => 'required', 'detail' => 'Reply body is required.', 'source' => ['pointer' => '/data/attributes/body']],
            ]);
        }
    }

    public function validatePreferences(UpdatePreferenceData $data): void
    {
        foreach ($data->preferences as $index => $preference) {
            $channel = $preference['channel'] ?? '';
            if (!in_array($channel, ['email', 'sms', 'push', 'in_app', 'webhook'], true)) {
                throw new ValidationException([
                    ['code' => 'invalid_channel', 'detail' => 'Invalid notification channel.', 'source' => ['pointer' => "/data/attributes/preferences/{$index}/channel"]],
                ]);
            }
        }
    }
}
