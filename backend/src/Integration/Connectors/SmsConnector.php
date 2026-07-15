<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class SmsConnector implements ConnectorContract
{
    public function type(): string { return 'sms'; }
    public function name(): string { return 'SMS Provider'; }
    public function description(): string { return 'SMS gateway for text notifications.'; }
    public function category(): string { return 'messaging'; }

    public function defaultConfig(): array
    {
        return [
            'provider' => '',
            'api_url' => '',
            'api_key' => '',
            'sender_id' => '',
        ];
    }

    public function test(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'SMS API key is not configured.'];
        }

        return ['success' => true, 'message' => 'SMS connector placeholder — no live API call made.'];
    }
}
