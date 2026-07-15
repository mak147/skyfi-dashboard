<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class DiscordConnector implements ConnectorContract
{
    public function type(): string { return 'discord'; }
    public function name(): string { return 'Discord'; }
    public function description(): string { return 'Discord webhook integration for alerts.'; }
    public function category(): string { return 'messaging'; }

    public function defaultConfig(): array
    {
        return [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ];
    }

    public function test(array $config): array
    {
        $webhookUrl = $config['webhook_url'] ?? '';
        if ($webhookUrl === '') {
            return ['success' => false, 'message' => 'Discord webhook URL is not configured.'];
        }

        return ['success' => true, 'message' => 'Discord connector placeholder — no live API call made.'];
    }
}
