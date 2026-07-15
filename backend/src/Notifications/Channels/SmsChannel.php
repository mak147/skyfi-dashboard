<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Channels;

use SkyFi\Notifications\Contracts\ChannelDriverContract;

final class SmsChannel implements ChannelDriverContract
{
    public function channel(): string
    {
        return 'sms';
    }

    public function send(array $payload): array
    {
        return [
            'status' => 'skipped',
            'provider' => 'sms_stub',
            'provider_message_id' => null,
            'fail_reason' => 'provider_placeholder',
            'body' => $payload['body'] ?? null,
            'subject' => $payload['subject'] ?? null,
        ];
    }
}
