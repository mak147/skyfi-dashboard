<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Services;

use SkyFi\Shared\Events\EventDispatcher;
use SkyFi\Workflow\Contracts\TriggerManagerContract;
use SkyFi\Workflow\Contracts\WorkflowEngineContract;
use SkyFi\Workflow\Contracts\WorkflowRepositoryContract;
use SkyFi\Workflow\Contracts\WorkflowVersionRepositoryContract;

final class TriggerManager implements TriggerManagerContract
{
    /** @var list<string> */
    private array $knownEvents = [];

    public function __construct(
        private readonly WorkflowRepositoryContract $workflows,
        private readonly WorkflowVersionRepositoryContract $versions,
        private readonly WorkflowEngineContract $engine,
    ) {}

    public function register(): void
    {
        $this->knownEvents = $this->defaultEvents();

        foreach ($this->knownEvents as $eventKey) {
            EventDispatcher::listen($eventKey, function (mixed $payload) use ($eventKey): void {
                $data = is_array($payload) ? $payload : ['payload' => $payload];
                if (!isset($data['event_key'])) {
                    $data['event_key'] = $eventKey;
                }
                try {
                    $this->handle($eventKey, $data);
                } catch (\Throwable) {
                    // Workflow failures must not break primary business transactions.
                }
            });
        }

        // Catch-all style: also listen for a generic workflow.event if modules emit it.
        EventDispatcher::listen('workflow.event', function (mixed $payload): void {
            if (!is_array($payload)) {
                return;
            }
            $eventKey = (string) ($payload['event_key'] ?? $payload['event'] ?? '');
            if ($eventKey === '') {
                return;
            }
            try {
                $this->handle($eventKey, $payload);
            } catch (\Throwable) {
            }
        });
    }

    public function handle(string $eventKey, array $payload): int
    {
        $matches = $this->workflows->findEnabledByEvent($eventKey);
        $count = 0;
        foreach ($matches as $workflow) {
            $versionId = $workflow->activeVersionId();
            if ($versionId === null) {
                continue;
            }
            $version = $this->versions->find($versionId);
            if ($version === null) {
                continue;
            }
            $this->engine->enqueue(
                $workflow,
                $version,
                $payload,
                'event',
                $eventKey,
                isset($payload['actor_id']) ? (int) $payload['actor_id'] : null,
            );
            $count++;
        }

        return $count;
    }

    /** @return list<string> */
    private function defaultEvents(): array
    {
        return [
            'customer.created', 'customer.updated', 'customer.deleted', 'customer.status_changed',
            'invoice.generated', 'invoice.updated', 'invoice.paid', 'invoice.overdue', 'invoice.voided',
            'payment.completed', 'payment.failed', 'payment.reversed', 'payment.refunded',
            'journal_entry.created', 'journal_entry.posted',
            'inventory.low_stock', 'inventory.stock_adjusted', 'inventory.transfer.completed', 'inventory.stock.posted',
            'purchasing.request.approved', 'purchasing.order.approved', 'purchasing.order.received',
            'vendor.contract.expiring', 'vendor.created',
            'support.ticket.created', 'support.ticket.assigned', 'support.ticket.resolved',
            'support.ticket.updated', 'support.ticket.replied',
            'monitoring.alert.triggered', 'monitoring.device.offline', 'monitoring.device.recovered',
            'pppoe.account.created', 'pppoe.account.disabled', 'pppoe.session.connected',
            'hotspot.user.created', 'hotspot.session.started',
            'field.installation.completed', 'field.work_order.completed', 'field.installation_request.completed',
            'connection.approved',
            'notification.dispatched', 'notification.delivery.failed',
        ];
    }
}
