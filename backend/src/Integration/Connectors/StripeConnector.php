<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class StripeConnector implements ConnectorContract
{
    public function type(): string { return 'stripe'; }
    public function name(): string { return 'Stripe'; }
    public function description(): string { return 'Stripe payment processing gateway.'; }
    public function category(): string { return 'payment'; }

    public function defaultConfig(): array
    {
        return [
            'api_key' => '',
            'webhook_secret' => '',
            'test_mode' => true,
        ];
    }

    public function test(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Stripe API key is not configured.'];
        }

        return ['success' => true, 'message' => 'Stripe connector placeholder — no live API call made.'];
    }
}
