<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class WhatsAppConnector implements ConnectorContract
{
    public function type(): string { return 'whatsapp'; }
    public function name(): string { return 'WhatsApp Business'; }
    public function description(): string { return 'WhatsApp Business API for notifications.'; }
    public function category(): string { return 'messaging'; }

    public function defaultConfig(): array
    {
        return [
            'api_url' => '',
            'phone_number_id' => '',
            'access_token' => '',
            'verify_token' => '',
        ];
    }

    public function test(array $config): array
    {
        $token = $config['access_token'] ?? '';
        if ($token === '') {
            return ['success' => false, 'message' => 'WhatsApp access token is not configured.'];
        }

        return ['success' => true, 'message' => 'WhatsApp connector placeholder — no live API call made.'];
    }
}
