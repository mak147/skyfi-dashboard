<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Channels;

use SkyFi\Notifications\Contracts\ChannelDriverContract;

final class InAppChannel implements ChannelDriverContract
{
    public function channel(): string
    {
        return 'in_app';
    }

    public function send(array $payload): array
    {
        return [
            'status' => 'sent',
            'provider' => 'in_app',
            'provider_message_id' => $payload['notification_uuid'] ?? null,
            'fail_reason' => null,
            'body' => $payload['body'] ?? null,
            'subject' => $payload['subject'] ?? null,
        ];
    }
}
