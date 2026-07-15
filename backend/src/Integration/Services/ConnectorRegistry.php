<?php

declare(strict_types=1);

namespace SkyFi\Integration\Services;

use SkyFi\Integration\Connectors\ConnectorContract;
use SkyFi\Integration\Connectors\DiscordConnector;
use SkyFi\Integration\Connectors\EasypaisaConnector;
use SkyFi\Integration\Connectors\EmailConnector;
use SkyFi\Integration\Connectors\GoogleMapsConnector;
use SkyFi\Integration\Connectors\JazzCashConnector;
use SkyFi\Integration\Connectors\SlackConnector;
use SkyFi\Integration\Connectors\SmsConnector;
use SkyFi\Integration\Connectors\StripeConnector;
use SkyFi\Integration\Connectors\WhatsAppConnector;

/**
 * Registry of all available connector implementations.
 */
final class ConnectorRegistry
{
    /** @var array<string, ConnectorContract> */
    private array $connectors = [];

    public function __construct()
    {
        $this->register(new StripeConnector());
        $this->register(new JazzCashConnector());
        $this->register(new EasypaisaConnector());
        $this->register(new WhatsAppConnector());
        $this->register(new EmailConnector());
        $this->register(new SmsConnector());
        $this->register(new GoogleMapsConnector());
        $this->register(new SlackConnector());
        $this->register(new DiscordConnector());
    }

    public function register(ConnectorContract $connector): void
    {
        $this->connectors[$connector->type()] = $connector;
    }

    public function get(string $type): ?ConnectorContract
    {
        return $this->connectors[$type] ?? null;
    }

    /** @return list<ConnectorContract> */
    public function all(): array
    {
        return array_values($this->connectors);
    }

    /** @return list<string> */
    public function types(): array
    {
        return array_keys($this->connectors);
    }

    /** @return list<string> */
    public function categories(): array
    {
        $cats = array_unique(array_map(
            static fn(ConnectorContract $c): string => $c->category(),
            $this->connectors,
        ));
        sort($cats);

        return $cats;
    }
}
