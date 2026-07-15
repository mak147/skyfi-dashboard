<?php

declare(strict_types=1);

namespace SkyFi\Workflow\EventSubscribers;

use SkyFi\Workflow\Contracts\TriggerManagerContract;

/**
 * Thin adapter so Container can register workflow event subscriptions
 * using the same pattern as Notifications and Integration modules.
 */
final class DomainEventSubscriber
{
    public function __construct(private readonly TriggerManagerContract $triggers) {}

    public function register(): void
    {
        $this->triggers->register();
    }
}
