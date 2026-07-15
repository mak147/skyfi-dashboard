<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Contracts\ApiKeyRepositoryContract;
use SkyFi\Integration\Contracts\ConnectorRepositoryContract;
use SkyFi\Integration\Contracts\EventRegistryRepositoryContract;
use SkyFi\Integration\Contracts\RequestLogRepositoryContract;
use SkyFi\Integration\Contracts\WebhookDeliveryRepositoryContract;
use SkyFi\Integration\Contracts\WebhookRepositoryContract;
use SkyFi\Integration\DTOs\ApiKeyListFilters;
use SkyFi\Integration\DTOs\DeliveryListFilters;
use SkyFi\Integration\DTOs\WebhookListFilters;

final class IntegrationDashboardService implements Contracts\IntegrationServiceContract
{
    public function __construct(
        private readonly ApiKeyRepositoryContract $keys,
        private readonly WebhookRepositoryContract $webhooks,
        private readonly WebhookDeliveryRepositoryContract $deliveries,
        private readonly EventRegistryRepositoryContract $events,
        private readonly ConnectorRepositoryContract $connectors,
        private readonly RequestLogRepositoryContract $logs,
    ) {}

    public function dashboard(): array
    {
        $apiKeyList = $this->keys->list(new ApiKeyListFilters(page: 1, perPage: 1));
        $activeKeyList = $this->keys->list(new ApiKeyListFilters(isActive: true, page: 1, perPage: 1));
        $webhookList = $this->webhooks->list(new WebhookListFilters(page: 1, perPage: 1));
        $outboundWebhooks = $this->webhooks->list(new WebhookListFilters(isInbound: false, page: 1, perPage: 1));
        $inboundWebhooks = $this->webhooks->list(new WebhookListFilters(isInbound: true, page: 1, perPage: 1));
        $failedDeliveries = $this->deliveries->list(new DeliveryListFilters(status: 'failed', page: 1, perPage: 1));
        $pendingRetries = count($this->deliveries->findPendingRetries());
        $eventKeys = $this->events->allActiveKeys();
        $sourceModules = $this->events->sourceModules();
        $connectors = $this->connectors->listAll();
        $enabledConnectors = array_filter($connectors, static fn($c): bool => $c->toArray()['is_enabled'] ?? false);
        $stats = $this->logs->aggregateStats();

        return [
            'api_keys' => [
                'total' => $apiKeyList['total'],
                'active' => $activeKeyList['total'],
            ],
            'webhooks' => [
                'total' => $webhookList['total'],
                'outbound' => $outboundWebhooks['total'],
                'inbound' => $inboundWebhooks['total'],
            ],
            'deliveries' => [
                'failed' => $failedDeliveries['total'],
                'pending_retries' => $pendingRetries,
            ],
            'events' => [
                'total' => count($eventKeys),
                'source_modules' => $sourceModules,
            ],
            'connectors' => [
                'total' => count($connectors),
                'enabled' => count($enabledConnectors),
            ],
            'request_stats' => $stats,
        ];
    }
}
