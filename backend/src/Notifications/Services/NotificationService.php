<?php

declare(strict_types=1);

namespace SkyFi\Notifications\Services;

use PDO;
use SkyFi\Notifications\Contracts\NotificationEventRepositoryContract;
use SkyFi\Notifications\Contracts\NotificationRepositoryContract;
use SkyFi\Notifications\Contracts\NotificationServiceContract;
use SkyFi\Notifications\Contracts\NotificationTemplateRepositoryContract;
use SkyFi\Notifications\DomainModels\Notification;
use SkyFi\Notifications\DTOs\DispatchNotificationData;
use SkyFi\Notifications\DTOs\NotificationListFilters;
use SkyFi\Notifications\EventPublishers\NotificationEventPublisher;
use SkyFi\Notifications\Validators\NotificationValidator;
use SkyFi\Shared\Exceptions\NotFoundException;

final class NotificationService implements NotificationServiceContract
{
    public function __construct(
        private readonly NotificationRepositoryContract $notifications,
        private readonly NotificationTemplateRepositoryContract $templates,
        private readonly NotificationEventRepositoryContract $events,
        private readonly DeliveryService $delivery,
        private readonly NotificationEventPublisher $publisher,
        private readonly NotificationCatalog $catalog,
        private readonly NotificationValidator $validator,
        private readonly PDO $pdo,
    ) {}

    public function list(int $userId, NotificationListFilters $filters): array
    {
        return $this->notifications->listForUser($userId, $filters);
    }

    public function get(int $id, int $userId): Notification
    {
        return $this->notifications->findForUser($id, $userId)
            ?? throw new NotFoundException('Notification not found.');
    }

    public function markRead(int $id, int $userId): Notification
    {
        return $this->notifications->markRead($id, $userId)
            ?? throw new NotFoundException('Notification not found.');
    }

    public function markAllRead(int $userId): int
    {
        return $this->notifications->markAllRead($userId);
    }

    public function archive(int $id, int $userId): Notification
    {
        return $this->notifications->archive($id, $userId)
            ?? throw new NotFoundException('Notification not found.');
    }

    public function delete(int $id, int $userId): void
    {
        if (!$this->notifications->softDelete($id, $userId)) {
            throw new NotFoundException('Notification not found.');
        }
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCount($userId);
    }

    public function catalog(): array
    {
        return $this->catalog->toArray();
    }

    public function dispatch(DispatchNotificationData $data): array
    {
        $this->validator->dispatch($data);
        $meta = $this->catalog->type($data->type);
        $category = $meta['category'] ?? 'system';
        $severity = $data->severity ?? $meta['severity'] ?? 'info';
        $actionUrl = $data->actionUrl ?? $meta['action_url'] ?? null;
        $sourceModule = $data->sourceModule ?? $meta['source_module'] ?? 'notifications';
        $channels = $data->channels !== [] ? $data->channels : ($meta['default_channels'] ?? ['in_app']);

        $template = $this->templates->findByCodeChannel($data->type, 'in_app');
        $isTransactional = $template ? (bool) ($template->toArray()['is_transactional'] ?? false) : false;
        // Also check any channel template for transactional flag
        if (!$isTransactional) {
            foreach ($channels as $ch) {
                $t = $this->templates->findByCodeChannel($data->type, $ch);
                if ($t && (int) ($t->toArray()['is_transactional'] ?? 0) === 1) {
                    $isTransactional = true;
                    break;
                }
            }
        }

        $eventUuid = $data->eventUuid ?: $this->uuidFromParts($data->type, $data->sourceId, $data->recipientUserIds);
        $event = $this->publisher->record(
            $data->sourceEvent ?? $data->type,
            $sourceModule,
            [
                'type' => $data->type,
                'data' => $data->data,
                'recipients' => $data->recipientUserIds,
                'channels' => $channels,
            ],
            $data->sourceId,
            $eventUuid,
        );

        // Idempotent: if already processed, return existing summary
        $eventAttrs = $event->toArray();
        if (($eventAttrs['status'] ?? '') === 'processed') {
            return [
                'event' => $eventAttrs,
                'created' => 0,
                'deliveries' => 0,
                'duplicate' => true,
            ];
        }

        $this->events->update($event->id(), ['status' => 'processing']);

        $created = 0;
        $deliveryCount = 0;
        $notifications = [];

        try {
            foreach ($data->recipientUserIds as $recipientId) {
                if ($recipientId <= 0) {
                    continue;
                }

                $vars = $this->flattenVars($data->data);
                $title = $template
                    ? $this->delivery->render((string) ($template->toArray()['subject_template'] ?? $data->type), $vars)
                    : ($vars['title'] ?? $data->type);
                $body = $template
                    ? $this->delivery->render((string) ($template->toArray()['body_template'] ?? ''), $vars)
                    : ($vars['body'] ?? json_encode($data->data));

                $notificationUuid = $this->uuid();
                $notification = null;
                if (in_array('in_app', $channels, true)) {
                    $notification = $this->notifications->create([
                        'uuid' => $notificationUuid,
                        'recipient_user_id' => $recipientId,
                        'recipient_type' => 'user',
                        'notification_type' => $data->type,
                        'category' => $category,
                        'title' => $title !== '' ? $title : $data->type,
                        'body' => $body !== '' ? $body : $data->type,
                        'data' => $data->data,
                        'severity' => $severity,
                        'action_url' => $actionUrl,
                        'status' => 'unread',
                        'source_module' => $sourceModule,
                        'source_event' => $data->sourceEvent ?? $data->type,
                        'source_id' => $data->sourceId,
                        'event_id' => $event->id(),
                        'created_by' => $data->actorId,
                    ]);
                    $created++;
                    $notifications[] = $notification->toArray();
                }

                foreach ($channels as $channel) {
                    $results = $this->delivery->deliver(
                        $data->type,
                        $category,
                        $recipientId,
                        $channel,
                        [
                            'subject' => $title,
                            'body' => $body,
                            'data' => $vars,
                            'notification_uuid' => $notification?->toArray()['uuid'] ?? $notificationUuid,
                        ],
                        $isTransactional,
                        $notification?->id(),
                        $event->id(),
                    );
                    $deliveryCount += count($results);
                }
            }

            $this->publisher->markProcessed($event->id());
        } catch (\Throwable $e) {
            $this->publisher->markFailed($event->id(), $e->getMessage());
            throw $e;
        }

        return [
            'event' => $this->events->find($event->id())?->toArray(),
            'created' => $created,
            'deliveries' => $deliveryCount,
            'notifications' => $notifications,
            'duplicate' => false,
        ];
    }

    public function fromDomain(string $eventKey, array $payload): array
    {
        $mapping = $this->mapDomainEvent($eventKey, $payload);
        if ($mapping === null) {
            return ['skipped' => true, 'reason' => 'unmapped_event', 'event_key' => $eventKey];
        }

        return $this->dispatch(new DispatchNotificationData(
            type: $mapping['type'],
            recipientUserIds: $mapping['recipients'],
            data: $mapping['data'],
            channels: $mapping['channels'],
            sourceModule: $mapping['source_module'],
            sourceEvent: $eventKey,
            sourceId: $mapping['source_id'],
            severity: $mapping['severity'],
            actionUrl: $mapping['action_url'],
            actorId: $mapping['actor_id'],
            eventUuid: $mapping['event_uuid'],
        ));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{
     *   type: string,
     *   recipients: list<int>,
     *   data: array<string, mixed>,
     *   channels: list<string>,
     *   source_module: string,
     *   source_id: ?string,
     *   severity: string,
     *   action_url: ?string,
     *   actor_id: ?int,
     *   event_uuid: string
     * }|null
     */
    private function mapDomainEvent(string $eventKey, array $payload): ?array
    {
        $type = match ($eventKey) {
            'invoice.generated' => 'invoice.generated',
            'payment.completed' => 'payment.received',
            'payment.reversed' => 'payment.reversed',
            'payment.failed' => 'payment.failed',
            'support.ticket.created' => 'support.ticket.created',
            'support.ticket.assigned' => 'support.ticket.assigned',
            'support.ticket.replied' => 'support.ticket.replied',
            'support.ticket.updated' => $this->mapSupportUpdated($payload),
            'monitoring.alert.triggered' => $this->mapMonitoringAlert($payload),
            'field.work_order.completed', 'field.installation_request.completed' => 'field.installation.completed',
            'purchasing.order.approved' => 'purchasing.order.approved',
            'purchasing.request.approved' => 'purchasing.request.approved',
            'inventory.stock.posted' => $this->mapInventoryLowStock($payload),
            'inventory.low_stock' => 'inventory.low_stock',
            'vendor.contract.expiring' => 'vendor.contract.expiring',
            'connection.approved' => 'connection.approved',
            default => null,
        };

        if ($type === null) {
            return null;
        }

        $meta = $this->catalog->type($type);
        if ($meta === null) {
            return null;
        }

        $sourceId = $this->extractSourceId($eventKey, $payload);
        $recipients = $this->resolveRecipients($eventKey, $type, $payload);
        if ($recipients === []) {
            $recipients = $this->usersWithRoles(['Super Administrator']);
        }

        $data = $this->buildDataPayload($type, $payload);
        $actorId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : (isset($payload['updated_by']) ? (int) $payload['updated_by'] : null);

        return [
            'type' => $type,
            'recipients' => $recipients,
            'data' => $data,
            'channels' => $meta['default_channels'],
            'source_module' => $meta['source_module'],
            'source_id' => $sourceId,
            'severity' => $meta['severity'],
            'action_url' => $this->resolveActionUrl($type, $payload, $meta['action_url']),
            'actor_id' => $actorId,
            'event_uuid' => $this->uuidFromParts($type, $sourceId, $recipients),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function mapSupportUpdated(array $payload): ?string
    {
        $status = (string) ($payload['status'] ?? '');
        if (in_array($status, ['resolved', 'closed'], true)) {
            return 'support.ticket.resolved';
        }

        return null;
    }

    /** @param array<string, mixed> $payload */
    private function mapMonitoringAlert(array $payload): ?string
    {
        $alertType = (string) ($payload['alert_type'] ?? '');
        return match ($alertType) {
            'device_offline' => 'monitoring.router_offline',
            'high_cpu' => 'monitoring.high_cpu',
            default => null,
        };
    }

    /** @param array<string, mixed> $payload */
    private function mapInventoryLowStock(array $payload): ?string
    {
        // Prefer explicit inventory.low_stock events; stock.posted may not include levels.
        if (isset($payload['is_low_stock']) && filter_var($payload['is_low_stock'], FILTER_VALIDATE_BOOLEAN)) {
            return 'inventory.low_stock';
        }
        if (isset($payload['quantity'], $payload['reorder_level'])
            && (float) $payload['quantity'] <= (float) $payload['reorder_level']) {
            return 'inventory.low_stock';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<int>
     */
    private function resolveRecipients(string $eventKey, string $type, array $payload): array
    {
        $ids = [];

        // Explicit assignee / actor fields used across modules
        foreach ([
            'assigned_to', 'assignee_user_id', 'assignee_id', 'assigned_staff_id', 'staff_user_id',
            'technician_user_id', 'requested_by', 'created_by', 'user_id', 'actor_id', 'assigned_agent_id',
        ] as $key) {
            if (isset($payload[$key]) && (int) $payload[$key] > 0) {
                $ids[] = (int) $payload[$key];
            }
        }
        if (isset($payload['connection']['created_by'])) {
            $ids[] = (int) $payload['connection']['created_by'];
        }

        $roleMap = match ($type) {
            'invoice.generated', 'payment.received', 'payment.failed', 'payment.reversed' => ['Finance Department', 'Company Owner'],
            'support.ticket.created', 'support.ticket.replied', 'support.ticket.resolved' => ['Customer Support', 'Regional Manager'],
            'monitoring.router_offline', 'monitoring.high_cpu' => ['Network Engineer', 'Regional Manager'],
            'field.installation.completed', 'field.installation.scheduled' => ['Installation Team / Field Technician', 'Regional Manager'],
            'inventory.low_stock' => ['Inventory Manager'],
            'purchasing.order.approved', 'purchasing.request.approved' => ['Inventory Manager', 'Finance Department'],
            'vendor.contract.expiring' => ['Inventory Manager', 'Company Owner'],
            'connection.approved' => ['Customer Support', 'Installation Team / Field Technician'],
            default => ['Super Administrator'],
        };

        // For assignment, prefer the assignee only
        if ($type === 'support.ticket.assigned' && $ids !== []) {
            return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        }

        $roleUsers = $this->usersWithRoles($roleMap);
        $merged = array_merge($ids, $roleUsers);

        return array_values(array_unique(array_filter($merged, static fn (int $id): bool => $id > 0)));
    }

    /** @param list<string> $roles @return list<int> */
    private function usersWithRoles(array $roles): array
    {
        if ($roles === []) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            $sql = "SELECT DISTINCT u.id
                    FROM users u
                    INNER JOIN role_user ru ON ru.user_id = u.id
                    INNER JOIN roles r ON r.id = ru.role_id
                    WHERE u.deleted_at IS NULL AND r.name IN ({$placeholders})
                    LIMIT 50";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($roles));
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        } catch (\Throwable) {
            // Fallback if pivot table naming differs
            try {
                $placeholders = implode(',', array_fill(0, count($roles), '?'));
                $sql = "SELECT DISTINCT u.id
                        FROM users u
                        INNER JOIN user_role ur ON ur.user_id = u.id
                        INNER JOIN roles r ON r.id = ur.role_id
                        WHERE u.deleted_at IS NULL AND r.name IN ({$placeholders})
                        LIMIT 50";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(array_values($roles));
                return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
            } catch (\Throwable) {
                return [];
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildDataPayload(string $type, array $payload): array
    {
        $connection = is_array($payload['connection'] ?? null) ? $payload['connection'] : [];

        return match ($type) {
            'invoice.generated' => [
                'invoice_number' => $payload['invoice_number'] ?? ('#' . ($payload['id'] ?? '')),
                'amount' => $payload['total_amount'] ?? $payload['amount'] ?? '',
                'customer_name' => $payload['customer_name'] ?? '',
                'id' => $payload['id'] ?? null,
            ],
            'payment.received', 'payment.reversed' => [
                'payment_number' => $payload['payment_number'] ?? ('#' . ($payload['id'] ?? '')),
                'amount' => $payload['amount'] ?? '',
                'id' => $payload['id'] ?? null,
            ],
            'payment.failed' => [
                'reason' => $payload['reason'] ?? $payload['fail_reason'] ?? 'Payment failed',
                'amount' => $payload['amount'] ?? '',
            ],
            'support.ticket.created', 'support.ticket.assigned', 'support.ticket.resolved', 'support.ticket.replied' => [
                'ticket_number' => $payload['ticket_number'] ?? ('#' . ($payload['id'] ?? $payload['ticket_id'] ?? '')),
                'subject' => $payload['subject'] ?? $payload['title'] ?? '',
                'priority' => $payload['priority'] ?? 'normal',
                'id' => $payload['id'] ?? $payload['ticket_id'] ?? null,
            ],
            'monitoring.router_offline', 'monitoring.high_cpu' => [
                'title' => $payload['title'] ?? 'Network alert',
                'description' => $payload['description'] ?? '',
                'device_id' => $payload['device_id'] ?? null,
                'metric_value' => $payload['metric_value'] ?? null,
            ],
            'field.installation.completed' => [
                'work_order_number' => $payload['work_order_number'] ?? ('#' . ($payload['id'] ?? '')),
                'customer_id' => $payload['customer_id'] ?? null,
            ],
            'field.installation.scheduled' => [
                'customer_name' => $payload['customer_name'] ?? '',
                'scheduled_at' => $payload['scheduled_at'] ?? $payload['scheduled_date'] ?? '',
            ],
            'inventory.low_stock' => [
                'product_name' => $payload['product_name'] ?? $payload['name'] ?? 'Product',
                'sku' => $payload['sku'] ?? '',
                'quantity' => $payload['quantity'] ?? $payload['total_stock'] ?? '',
                'reorder_level' => $payload['reorder_level'] ?? '',
            ],
            'purchasing.order.approved' => [
                'order_number' => $payload['order_number'] ?? $payload['po_number'] ?? ('#' . ($payload['id'] ?? '')),
                'total_amount' => $payload['total_amount'] ?? '',
            ],
            'purchasing.request.approved' => [
                'request_number' => $payload['request_number'] ?? ('#' . ($payload['id'] ?? '')),
            ],
            'vendor.contract.expiring' => [
                'contract_number' => $payload['contract_number'] ?? '',
                'supplier_name' => $payload['supplier_name'] ?? '',
                'days_remaining' => $payload['days_remaining'] ?? '',
            ],
            'connection.approved' => [
                'connection_id' => $connection['id'] ?? $payload['connection_id'] ?? $payload['id'] ?? '',
                'customer_id' => $connection['customer_id'] ?? $payload['customer_id'] ?? '',
            ],
            default => $payload,
        };
    }

    /** @param array<string, mixed> $payload */
    private function extractSourceId(string $eventKey, array $payload): ?string
    {
        if ($eventKey === 'connection.approved') {
            $id = $payload['connection']['id'] ?? $payload['connection_id'] ?? null;
            return $id !== null ? (string) $id : null;
        }
        if ($eventKey === 'support.ticket.replied') {
            $id = $payload['ticket_id'] ?? $payload['comment']['ticket_id'] ?? null;
            return $id !== null ? (string) $id : null;
        }
        $id = $payload['id'] ?? $payload['source_id'] ?? null;

        return $id !== null ? (string) $id : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveActionUrl(string $type, array $payload, ?string $default): ?string
    {
        $id = $payload['id'] ?? $payload['ticket_id'] ?? null;
        return match ($type) {
            'support.ticket.created', 'support.ticket.assigned', 'support.ticket.resolved', 'support.ticket.replied'
                => $id ? '/support/tickets/' . $id : '/support',
            'invoice.generated' => $id ? '/billing/invoices/' . $id : '/billing',
            'payment.received', 'payment.reversed', 'payment.failed' => $id ? '/payments/' . $id : '/payments',
            'field.installation.completed' => $id ? '/field-service/work-orders/' . $id : '/field-service',
            default => $default,
        };
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function flattenVars(array $data): array
    {
        $flat = $data;
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $flat[(string) $key] = $value;
            }
        }

        return $flat;
    }

    /** @param list<int> $recipients */
    private function uuidFromParts(string $type, ?string $sourceId, array $recipients): string
    {
        $material = $type . '|' . ($sourceId ?? '') . '|' . implode(',', $recipients) . '|' . gmdate('Y-m-d-H');
        $hash = md5($material);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
