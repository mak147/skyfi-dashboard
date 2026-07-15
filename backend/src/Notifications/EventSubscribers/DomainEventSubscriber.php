<?php

declare(strict_types=1);

namespace SkyFi\Notifications\EventSubscribers;

use SkyFi\Notifications\Contracts\NotificationServiceContract;
use SkyFi\Shared\Events\EventDispatcher;

/**
 * Subscribes to domain events from other modules and routes them through
 * the Notification Center. Business modules continue to dispatch events
 * without knowing about notification delivery.
 */
final class DomainEventSubscriber
{
    public function __construct(private readonly NotificationServiceContract $notifications) {}

    public function register(): void
    {
        $events = [
            'invoice.generated',
            'payment.completed',
            'payment.reversed',
            'payment.failed',
            'support.ticket.created',
            'support.ticket.assigned',
            'support.ticket.updated',
            'support.ticket.replied',
            'monitoring.alert.triggered',
            'field.work_order.completed',
            'field.installation_request.completed',
            'purchasing.order.approved',
            'purchasing.request.approved',
            'inventory.stock.posted',
            'inventory.low_stock',
            'vendor.contract.expiring',
            'connection.approved',
        ];

        foreach ($events as $event) {
            EventDispatcher::listen($event, function (mixed $payload) use ($event): void {
                $data = is_array($payload) ? $payload : ['payload' => $payload];
                try {
                    $this->notifications->fromDomain($event, $data);
                } catch (\Throwable) {
                    // Notification failures must not break primary business transactions.
                }
            });
        }
    }
}
