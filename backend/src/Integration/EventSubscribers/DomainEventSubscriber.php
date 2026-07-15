<?php

declare(strict_types=1);

namespace SkyFi\Integration\EventSubscribers;

use SkyFi\Integration\Contracts\WebhookDispatcherContract;
use SkyFi\Integration\Contracts\EventRegistryRepositoryContract;
use SkyFi\Shared\Events\EventDispatcher;

final class DomainEventSubscriber
{
    /** @var list<string> */
    private array $registeredEvents = [];

    public function __construct(
        private readonly WebhookDispatcherContract $dispatcher,
        private readonly EventRegistryRepositoryContract $events,
    ) {}

    /**
     * Register listeners for all active events in the event registry.
     */
    public function register(): void
    {
        $this->registeredEvents = $this->events->allActiveKeys();

        foreach ($this->registeredEvents as $eventKey) {
            EventDispatcher::listen($eventKey, function (mixed $payload): void {
                if (!is_array($payload)) {
                    return;
                }
                // Determine event key from the listener context
                // We dispatch for all registered events that fire
                $this->handleEvent($payload);
            });
        }
    }

    /**
     * Handle a domain event by dispatching to matching webhooks.
     * Uses the event_key from the payload if available.
     *
     * @param array<string, mixed> $payload
     */
    private function handleEvent(array $payload): void
    {
        $eventKey = $payload['event_key'] ?? $payload['event'] ?? '';
        if ($eventKey === '') {
            return;
        }

        $this->dispatcher->dispatch($eventKey, $payload);
    }

    /**
     * Manually dispatch a known event. Used when event context is clear.
     *
     * @param array<string, mixed> $payload
     */
    public function dispatchEvent(string $eventKey, array $payload): void
    {
        if (in_array($eventKey, $this->registeredEvents, true)) {
            $this->dispatcher->dispatch($eventKey, $payload);
        }
    }

    /** @return list<string> */
    public function getRegisteredEvents(): array
    {
        return $this->registeredEvents;
    }
}
