<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Validators;

use SkyFi\Notifications\DTOs\DispatchNotificationData;
use SkyFi\Notifications\Services\NotificationCatalog;
use SkyFi\Shared\Exceptions\ValidationException;

final class NotificationValidator
{
    public function __construct(private readonly NotificationCatalog $catalog) {}

    public function dispatch(DispatchNotificationData $data): void
    {
        $errors = [];
        if ($data->type === '' || !$this->catalog->has($data->type)) {
            $errors[] = ['code' => 'invalid_type', 'detail' => 'Unknown notification type.', 'source' => ['pointer' => '/data/attributes/type']];
        }
        if ($data->recipientUserIds === []) {
            $errors[] = ['code' => 'recipients_required', 'detail' => 'At least one recipient is required.', 'source' => ['pointer' => '/data/attributes/recipient_user_ids']];
        }
        $validChannels = $this->catalog->channels();
        foreach ($data->channels as $channel) {
            if (!in_array($channel, $validChannels, true)) {
                $errors[] = ['code' => 'invalid_channel', 'detail' => "Invalid channel: {$channel}.", 'source' => ['pointer' => '/data/attributes/channels']];
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
