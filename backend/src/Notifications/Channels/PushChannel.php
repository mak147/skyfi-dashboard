<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Channels;

use SkyFi\Notifications\Contracts\ChannelDriverContract;

final class PushChannel implements ChannelDriverContract
{
    public function channel(): string
    {
        return 'push';
    }

    public function send(array $payload): array
    {
        return [
            'status' => 'skipped',
            'provider' => 'push_stub',
            'provider_message_id' => null,
            'fail_reason' => 'provider_placeholder',
            'body' => $payload['body'] ?? null,
            'subject' => $payload['subject'] ?? null,
        ];
    }
}
