<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Services;

final class NotificationCatalog
{
    /** @return list<string> */
    public function channels(): array
    {
        return ['in_app', 'email', 'sms', 'push', 'webhook'];
    }

    /** @return list<string> */
    public function categories(): array
    {
        return ['billing', 'payments', 'support', 'network', 'inventory', 'purchasing', 'vendors', 'field', 'system'];
    }

    /**
     * @return array<string, array{category: string, severity: string, default_channels: list<string>, action_url: ?string, source_module: string}>
     */
    public function types(): array
    {
        return [
            'invoice.generated' => [
                'category' => 'billing',
                'severity' => 'info',
                'default_channels' => ['in_app', 'email'],
                'action_url' => '/billing',
                'source_module' => 'billing',
            ],
            'payment.received' => [
                'category' => 'payments',
                'severity' => 'success',
                'default_channels' => ['in_app', 'email'],
                'action_url' => '/payments',
                'source_module' => 'payments',
            ],
            'payment.failed' => [
                'category' => 'payments',
                'severity' => 'warning',
                'default_channels' => ['in_app', 'email', 'sms'],
                'action_url' => '/payments',
                'source_module' => 'payments',
            ],
            'payment.reversed' => [
                'category' => 'payments',
                'severity' => 'warning',
                'default_channels' => ['in_app'],
                'action_url' => '/payments',
                'source_module' => 'payments',
            ],
            'support.ticket.created' => [
                'category' => 'support',
                'severity' => 'info',
                'default_channels' => ['in_app'],
                'action_url' => '/support',
                'source_module' => 'support',
            ],
            'support.ticket.assigned' => [
                'category' => 'support',
                'severity' => 'info',
                'default_channels' => ['in_app', 'email'],
                'action_url' => '/support',
                'source_module' => 'support',
            ],
            'support.ticket.resolved' => [
                'category' => 'support',
                'severity' => 'success',
                'default_channels' => ['in_app'],
                'action_url' => '/support',
                'source_module' => 'support',
            ],
            'support.ticket.replied' => [
                'category' => 'support',
                'severity' => 'info',
                'default_channels' => ['in_app'],
                'action_url' => '/support',
                'source_module' => 'support',
            ],
            'monitoring.router_offline' => [
                'category' => 'network',
                'severity' => 'critical',
                'default_channels' => ['in_app', 'email'],
                'action_url' => '/network/routers',
                'source_module' => 'monitoring',
            ],
            'monitoring.high_cpu' => [
                'category' => 'network',
                'severity' => 'warning',
                'default_channels' => ['in_app'],
                'action_url' => '/network/routers',
                'source_module' => 'monitoring',
            ],
            'field.installation.scheduled' => [
                'category' => 'field',
                'severity' => 'info',
                'default_channels' => ['in_app'],
                'action_url' => '/field-service',
                'source_module' => 'field',
            ],
            'field.installation.completed' => [
                'category' => 'field',
                'severity' => 'success',
                'default_channels' => ['in_app'],
                'action_url' => '/field-service',
                'source_module' => 'field',
            ],
            'inventory.low_stock' => [
                'category' => 'inventory',
                'severity' => 'warning',
                'default_channels' => ['in_app', 'email'],
                'action_url' => '/inventory/products',
                'source_module' => 'inventory',
            ],
            'purchasing.order.approved' => [
                'category' => 'purchasing',
                'severity' => 'info',
                'default_channels' => ['in_app'],
                'action_url' => '/purchasing/orders',
                'source_module' => 'purchasing',
            ],
            'purchasing.request.approved' => [
                'category' => 'purchasing',
                'severity' => 'info',
                'default_channels' => ['in_app'],
                'action_url' => '/purchasing/requests',
                'source_module' => 'purchasing',
            ],
            'vendor.contract.expiring' => [
                'category' => 'vendors',
                'severity' => 'warning',
                'default_channels' => ['in_app', 'email'],
                'action_url' => '/vendors/contracts',
                'source_module' => 'vendors',
            ],
            'connection.approved' => [
                'category' => 'system',
                'severity' => 'info',
                'default_channels' => ['in_app'],
                'action_url' => '/connections',
                'source_module' => 'connections',
            ],
        ];
    }

    public function has(string $type): bool
    {
        return isset($this->types()[$type]);
    }

    /** @return array{category: string, severity: string, default_channels: list<string>, action_url: ?string, source_module: string}|null */
    public function type(string $code): ?array
    {
        return $this->types()[$code] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'channels' => $this->channels(),
            'categories' => $this->categories(),
            'types' => $this->types(),
            'severities' => ['info', 'success', 'warning', 'critical'],
        ];
    }
}
