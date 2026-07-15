<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class JazzCashConnector implements ConnectorContract
{
    public function type(): string { return 'jazzcash'; }
    public function name(): string { return 'JazzCash'; }
    public function description(): string { return 'JazzCash mobile wallet payment gateway.'; }
    public function category(): string { return 'payment'; }

    public function defaultConfig(): array
    {
        return [
            'merchant_id' => '',
            'password' => '',
            'integrity_salt' => '',
            'api_url' => '',
        ];
    }

    public function test(array $config): array
    {
        $merchantId = $config['merchant_id'] ?? '';
        if ($merchantId === '') {
            return ['success' => false, 'message' => 'JazzCash Merchant ID is not configured.'];
        }

        return ['success' => true, 'message' => 'JazzCash connector placeholder — no live API call made.'];
    }
}
