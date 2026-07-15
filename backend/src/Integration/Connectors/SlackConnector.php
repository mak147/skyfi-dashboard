<?php

declare(strict_types=1);

namespace SkyFi\Integration\Connectors;

final class SlackConnector implements ConnectorContract
{
    public function type(): string { return 'slack'; }
    public function name(): string { return 'Slack'; }
    public function description(): string { return 'Slack workspace integration for alerts and notifications.'; }
    public function category(): string { return 'messaging'; }

    public function defaultConfig(): array
    {
        return [
            'webhook_url' => '',
            'channel' => '',
            'bot_name' => '',
        ];
    }

    public function test(array $config): array
    {
        $webhookUrl = $config['webhook_url'] ?? '';
        if ($webhookUrl === '') {
            return ['success' => false, 'message' => 'Slack webhook URL is not configured.'];
        }

        return ['success' => true, 'message' => 'Slack connector placeholder — no live API call made.'];
    }
}
