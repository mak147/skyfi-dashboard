<?php

declare(strict_types=1);

namespace SkyFi\Portal\DTOs;

final class PortalDashboardData
{
    /**
     * @param array<string, mixed> $customer
     * @param array<string, mixed>|null $connection
     * @param array<string, mixed>|null $package
     * @param array<string, mixed>|null $latestInvoice
     * @param array<int, array<string, mixed>> $recentPayments
     * @param array<int, array<string, mixed>> $activeTickets
     * @param array<int, array<string, mixed>> $recentNotifications
     */
    public function __construct(
        public readonly array $customer,
        public readonly ?array $connection,
        public readonly ?array $package,
        public readonly ?array $latestInvoice,
        public readonly array $recentPayments,
        public readonly array $activeTickets,
        public readonly array $recentNotifications,
        public readonly float $outstandingBalance,
        public readonly bool $isOnline,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'customer' => $this->customer,
            'connection' => $this->connection,
            'package' => $this->package,
            'latest_invoice' => $this->latestInvoice,
            'recent_payments' => $this->recentPayments,
            'active_tickets' => $this->activeTickets,
            'recent_notifications' => $this->recentNotifications,
            'outstanding_balance' => $this->outstandingBalance,
            'is_online' => $this->isOnline,
        ];
    }
}
