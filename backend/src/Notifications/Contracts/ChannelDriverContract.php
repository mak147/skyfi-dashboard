<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Contracts;

interface ChannelDriverContract
{
    public function channel(): string;

    /**
     * @param array<string, mixed> $payload
     * @return array{status: string, provider: string, provider_message_id: ?string, fail_reason: ?string, body: ?string, subject: ?string}
     */
    public function send(array $payload): array;
}
