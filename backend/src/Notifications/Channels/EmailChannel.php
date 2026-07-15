<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Channels;

use SkyFi\Notifications\Contracts\ChannelDriverContract;

/**
 * Email channel stub. Records a successful "sent" delivery via email_stub provider.
 * Real SMTP/SendGrid integration is deferred to System email_settings wiring.
 */
final class EmailChannel implements ChannelDriverContract
{
    public function channel(): string
    {
        return 'email';
    }

    public function send(array $payload): array
    {
        return [
            'status' => 'sent',
            'provider' => 'email_stub',
            'provider_message_id' => 'email-stub-' . bin2hex(random_bytes(6)),
            'fail_reason' => null,
            'body' => $payload['body'] ?? null,
            'subject' => $payload['subject'] ?? null,
        ];
    }
}
