<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class EmailConnector implements ConnectorContract
{
    public function type(): string { return 'email'; }
    public function name(): string { return 'Email Provider'; }
    public function description(): string { return 'SMTP or API-based email delivery.'; }
    public function category(): string { return 'messaging'; }

    public function defaultConfig(): array
    {
        return [
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'from_address' => '',
            'from_name' => '',
        ];
    }

    public function test(array $config): array
    {
        $host = $config['host'] ?? '';
        if ($host === '') {
            return ['success' => false, 'message' => 'Email SMTP host is not configured.'];
        }

        return ['success' => true, 'message' => 'Email connector placeholder — no live connection made.'];
    }
}
