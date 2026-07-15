<?php

declare(strict_types=1);

namespace SkyFi\Audit\Services;

use SkyFi\Audit\Contracts\AuditServiceContract;
use SkyFi\Shared\Events\EventDispatcher;

final class AuditEventSubscriber
{
    /** @var array<string, string> Maps domain events to audit action names */
    private const EVENT_ACTION_MAP = [
        'invoice.generated' => 'invoice.created',
        'payment.completed' => 'payment.received',
        'payment.reversed' => 'payment.refund',
        'payment.failed' => 'payment.failed',
        'connection.approved' => 'connection.approved',
        'support.ticket.created' => 'support.ticket_created',
        'support.ticket.assigned' => 'support.ticket_assigned',
        'support.ticket.replied' => 'support.ticket_replied',
        'monitoring.alert.triggered' => 'network.monitoring_alert',
        'field.work_order.completed' => 'field.work_order_completed',
        'field.installation_request.completed' => 'field.installation_completed',
        'purchasing.order.approved' => 'purchasing.approval',
        'purchasing.request.approved' => 'purchasing.request_approved',
        'inventory.stock.posted' => 'inventory.stock_in',
        'vendor.contract.expiring' => 'vendor.contract_expiring',
    ];

    /** @var array<string, string> Maps domain events to audit modules */
    private const EVENT_MODULE_MAP = [
        'invoice.generated' => 'billing',
        'payment.completed' => 'payments',
        'payment.reversed' => 'payments',
        'payment.failed' => 'payments',
        'connection.approved' => 'connections',
        'support.ticket.created' => 'support',
        'support.ticket.assigned' => 'support',
        'support.ticket.replied' => 'support',
        'monitoring.alert.triggered' => 'monitoring',
        'field.work_order.completed' => 'field_service',
        'field.installation_request.completed' => 'field_service',
        'purchasing.order.approved' => 'purchasing',
        'purchasing.request.approved' => 'purchasing',
        'inventory.stock.posted' => 'inventory',
        'vendor.contract.expiring' => 'vendors',
    ];

    public function __construct(
        private readonly AuditServiceContract $auditService,
    ) {}

    public function register(): void
    {
        foreach (self::EVENT_ACTION_MAP as $eventKey => $auditAction) {
            EventDispatcher::listen($eventKey, function (array $payload) use ($eventKey, $auditAction): void {
                $this->handleDomainEvent($eventKey, $auditAction, $payload);
            });
        }
    }

    private function handleDomainEvent(string $eventKey, string $auditAction, array $payload): void
    {
        $module = self::EVENT_MODULE_MAP[$eventKey] ?? 'system';
        $userId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : null;
        $entityType = $payload['entity_type'] ?? $this->inferEntityType($eventKey);
        $entityId = isset($payload['id']) ? (int) $payload['id'] : ($payload['entity_id'] ?? null);
        $severity = $this->inferSeverity($auditAction);

        $this->auditService->log(
            userId: $userId,
            action: $auditAction,
            entityType: $entityType,
            entityId: is_int($entityId) ? $entityId : null,
            module: $module,
            severity: $severity,
            ipAddress: $payload['ip_address'] ?? null,
            userAgent: $payload['user_agent'] ?? null,
            oldValues: $payload['old_values'] ?? null,
            newValues: $payload['new_values'] ?? $payload,
            correlationId: $payload['correlation_id'] ?? null,
            url: $payload['url'] ?? null,
            complianceTags: $payload['compliance_tags'] ?? null,
        );
    }

    private function inferEntityType(string $eventKey): string
    {
        $parts = explode('.', $eventKey);
        return $parts[0] ?? 'unknown';
    }

    private function inferSeverity(string $action): string
    {
        if (str_contains($action, 'delete') || str_contains($action, 'failed')) {
            return 'critical';
        }
        if (str_contains($action, 'changed') || str_contains($action, 'update') || str_contains($action, 'alert')) {
            return 'warning';
        }
        return 'info';
    }
}
