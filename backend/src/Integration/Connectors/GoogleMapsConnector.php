<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class GoogleMapsConnector implements ConnectorContract
{
    public function type(): string { return 'google_maps'; }
    public function name(): string { return 'Google Maps'; }
    public function description(): string { return 'Google Maps API for geocoding and mapping.'; }
    public function category(): string { return 'mapping'; }

    public function defaultConfig(): array
    {
        return [
            'api_key' => '',
            'geocoding_enabled' => true,
        ];
    }

    public function test(array $config): array
    {
        $apiKey = $config['api_key'] ?? '';
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'Google Maps API key is not configured.'];
        }

        return ['success' => true, 'message' => 'Google Maps connector placeholder — no live API call made.'];
    }
}
