<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class EasypaisaConnector implements ConnectorContract
{
    public function type(): string { return 'easypaisa'; }
    public function name(): string { return 'Easypaisa'; }
    public function description(): string { return 'Easypaisa mobile wallet payment gateway.'; }
    public function category(): string { return 'payment'; }

    public function defaultConfig(): array
    {
        return [
            'merchant_id' => '',
            'store_id' => '',
            'hash_key' => '',
            'api_url' => '',
        ];
    }

    public function test(array $config): array
    {
        $merchantId = $config['merchant_id'] ?? '';
        if ($merchantId === '') {
            return ['success' => false, 'message' => 'Easypaisa Merchant ID is not configured.'];
        }

        return ['success' => true, 'message' => 'Easypaisa connector placeholder — no live API call made.'];
    }
}
