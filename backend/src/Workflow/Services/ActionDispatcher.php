<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Services;

use SkyFi\Billing\Data\GenerateInvoiceData;
use SkyFi\Billing\Services\InvoiceService;
use SkyFi\Connections\Services\ConnectionService;
use SkyFi\Customers\Data\UpdateCustomerData;
use SkyFi\Customers\Services\CustomerService;
use SkyFi\FieldService\Services\WorkOrderService;
use SkyFi\Integration\Contracts\WebhookDispatcherContract;
use SkyFi\Notifications\DTOs\DispatchNotificationData;
use SkyFi\Notifications\Services\NotificationService;
use SkyFi\Pppoe\Services\PppoeService;
use SkyFi\Support\DTOs\AssignmentData;
use SkyFi\Support\DTOs\CreateTicketData;
use SkyFi\Support\Services\TicketService;
use SkyFi\Workflow\Contracts\ActionDispatcherContract;

final class ActionDispatcher implements ActionDispatcherContract
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly TicketService $tickets,
        private readonly WorkOrderService $workOrders,
        private readonly InvoiceService $invoices,
        private readonly ConnectionService $connections,
        private readonly PppoeService $pppoe,
        private readonly CustomerService $customers,
        private readonly WebhookDispatcherContract $webhooks,
    ) {}

    public function dispatch(array $action, array $payload, ?int $actorUserId, bool $dryRun = false): array
    {
        $type = (string) ($action['type'] ?? $action['action_type'] ?? '');
        $config = $action['config'] ?? $action['config_json'] ?? [];
        if (!is_array($config)) {
            $config = [];
        }
        $name = (string) ($action['name'] ?? $type);
        $started = microtime(true);

        try {
            if ($dryRun) {
                return [
                    'action' => $type,
                    'name' => $name,
                    'status' => 'dry_run',
                    'result' => [
                        'message' => 'Dry-run: action would execute with resolved configuration.',
                        'resolved_config' => $this->previewConfig($type, $config, $payload),
                    ],
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                ];
            }

            $result = match ($type) {
                'create_notification' => $this->createNotification($config, $payload, $actorUserId, ['in_app']),
                'send_email' => $this->createNotification($config, $payload, $actorUserId, ['email']),
                'send_sms' => $this->createNotification($config, $payload, $actorUserId, ['sms']),
                'create_support_ticket' => $this->createTicket($config, $payload, $actorUserId),
                'assign_technician' => $this->assignTechnician($config, $payload, $actorUserId),
                'generate_invoice' => $this->generateInvoice($config, $payload, $actorUserId),
                'activate_connection' => $this->changeConnectionStatus($config, $payload, $actorUserId, 'active'),
                'suspend_connection' => $this->suspendConnection($config, $payload, $actorUserId),
                'unsuspend_connection' => $this->unsuspendConnection($config, $payload, $actorUserId),
                'update_customer' => $this->updateCustomer($config, $payload, $actorUserId),
                'create_task' => $this->createTaskPlaceholder($config, $payload),
                'execute_webhook' => $this->executeWebhook($config, $payload),
                'call_internal_api' => $this->callInternalApi($config, $payload, $actorUserId),
                default => throw new \InvalidArgumentException("Unsupported workflow action: {$type}"),
            };

            return [
                'action' => $type,
                'name' => $name,
                'status' => 'success',
                'result' => $result,
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ];
        } catch (\Throwable $e) {
            return [
                'action' => $type,
                'name' => $name,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            ];
        }
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @param list<string> $defaultChannels
     * @return array<string, mixed>
     */
    private function createNotification(array $config, array $payload, ?int $actorUserId, array $defaultChannels): array
    {
        $recipients = $config['recipient_user_ids'] ?? [];
        if (!is_array($recipients) || $recipients === []) {
            $path = (string) ($config['recipient_user_ids_path'] ?? 'recipient_user_ids');
            $resolved = $this->resolve($payload, $path);
            $recipients = is_array($resolved) ? $resolved : [];
        }
        if ($recipients === [] && $actorUserId) {
            $recipients = [$actorUserId];
        }
        $channels = $config['channels'] ?? $defaultChannels;
        if (!is_array($channels)) {
            $channels = $defaultChannels;
        }
        $data = is_array($config['data'] ?? null) ? $config['data'] : [];
        $data = array_merge($payload, $data);

        $result = $this->notifications->dispatch(new DispatchNotificationData(
            type: (string) ($config['type'] ?? 'workflow.notification'),
            recipientUserIds: array_values(array_map('intval', $recipients)),
            data: $data,
            channels: array_values(array_map('strval', $channels)),
            sourceModule: 'workflow',
            sourceEvent: (string) ($payload['event_key'] ?? $payload['event'] ?? 'workflow.action'),
            sourceId: isset($payload['id']) ? (string) $payload['id'] : null,
            severity: isset($config['severity']) ? (string) $config['severity'] : 'info',
            actionUrl: isset($config['action_url']) ? (string) $config['action_url'] : null,
            actorId: $actorUserId,
        ));

        return is_array($result) ? $result : ['dispatched' => true];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function createTicket(array $config, array $payload, ?int $actorUserId): array
    {
        $customerId = $this->resolveInt($config, $payload, 'customer_id', 'customer_id_path', 'customer_id');
        $ticket = $this->tickets->create(CreateTicketData::fromArray([
            'customer_id' => $customerId,
            'connection_id' => $this->resolveInt($config, $payload, 'connection_id', 'connection_id_path', 'connection_id') ?: null,
            'category_id' => (int) ($config['category_id'] ?? 1),
            'priority' => (string) ($config['priority'] ?? 'normal'),
            'source' => (string) ($config['source'] ?? 'workflow'),
            'subject' => $this->interpolate((string) ($config['subject'] ?? 'Workflow ticket'), $payload),
            'description' => $this->interpolate((string) ($config['description'] ?? 'Created by workflow automation.'), $payload),
        ]), $actorUserId ?? 1);

        return method_exists($ticket, 'toArray') ? $ticket->toArray() : ['created' => true];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function assignTechnician(array $config, array $payload, ?int $actorUserId): array
    {
        $target = (string) ($config['target'] ?? 'work_order');
        $technicianId = $this->resolveInt($config, $payload, 'technician_id', 'technician_id_path', 'technician_id');
        $actor = $actorUserId ?? 1;

        if ($target === 'ticket') {
            $ticketId = $this->resolveInt($config, $payload, 'ticket_id', 'ticket_id_path', 'id');
            $assignment = AssignmentData::fromArray([
                'staff_user_id' => $technicianId,
                'team_id' => $config['team_id'] ?? null,
                'reason' => (string) ($config['notes'] ?? 'Assigned by workflow automation.'),
            ]);
            $result = $this->tickets->assign($ticketId, $assignment, $actor);

            return is_array($result) ? $result : (method_exists($result, 'toArray') ? $result->toArray() : ['assigned' => true]);
        }

        $workOrderId = $this->resolveInt($config, $payload, 'work_order_id', 'work_order_id_path', 'id');
        $result = $this->workOrders->assign($workOrderId, [
            'technician_id' => $technicianId,
            'team_id' => $config['team_id'] ?? null,
        ], $actor);

        return is_array($result) ? $result : ['assigned' => true];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function generateInvoice(array $config, array $payload, ?int $actorUserId): array
    {
        $connectionId = $this->resolveInt($config, $payload, 'connection_id', 'connection_id_path', 'connection_id');
        $data = GenerateInvoiceData::fromArray([
            'connection_id' => $connectionId,
            'billing_period_start' => $config['billing_period_start'] ?? date('Y-m-01'),
            'billing_period_end' => $config['billing_period_end'] ?? date('Y-m-t'),
            'issue_date' => $config['issue_date'] ?? date('Y-m-d'),
            'due_date' => $config['due_date'] ?? date('Y-m-d', strtotime('+7 days')),
            'notes' => $config['notes'] ?? 'Generated by workflow automation.',
        ]);
        $invoice = $this->invoices->generate($data, $actorUserId ?? 1, null, null);

        return method_exists($invoice, 'toArray') ? $invoice->toArray() : ['generated' => true];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function changeConnectionStatus(array $config, array $payload, ?int $actorUserId, string $status): array
    {
        $connectionId = $this->resolveInt($config, $payload, 'connection_id', 'connection_id_path', 'connection_id');
        $connection = $this->connections->changeStatus($connectionId, $status, $actorUserId ?? 1, null, null);

        return method_exists($connection, 'toArray') ? $connection->toArray() : ['status' => $status];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function suspendConnection(array $config, array $payload, ?int $actorUserId): array
    {
        $result = ['connection' => null, 'pppoe' => null];
        $connectionId = $this->optionalInt($config, $payload, 'connection_id', 'connection_id_path', 'connection_id');
        if ($connectionId) {
            $connection = $this->connections->changeStatus($connectionId, 'suspended', $actorUserId ?? 1, null, null);
            $result['connection'] = method_exists($connection, 'toArray') ? $connection->toArray() : ['id' => $connectionId];
        }
        $pppoeId = $this->optionalInt($config, $payload, 'pppoe_account_id', 'pppoe_account_id_path', 'pppoe_account_id');
        if ($pppoeId) {
            $account = $this->pppoe->suspend($pppoeId, $actorUserId ?? 1, null, null);
            $result['pppoe'] = method_exists($account, 'toArray') ? $account->toArray() : ['id' => $pppoeId];
        }
        if (!$connectionId && !$pppoeId) {
            throw new \InvalidArgumentException('suspend_connection requires connection_id or pppoe_account_id.');
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function unsuspendConnection(array $config, array $payload, ?int $actorUserId): array
    {
        $result = ['connection' => null, 'pppoe' => null];
        $connectionId = $this->optionalInt($config, $payload, 'connection_id', 'connection_id_path', 'connection_id');
        if ($connectionId) {
            $connection = $this->connections->changeStatus($connectionId, 'active', $actorUserId ?? 1, null, null);
            $result['connection'] = method_exists($connection, 'toArray') ? $connection->toArray() : ['id' => $connectionId];
        }
        $pppoeId = $this->optionalInt($config, $payload, 'pppoe_account_id', 'pppoe_account_id_path', 'pppoe_account_id');
        if ($pppoeId) {
            $account = $this->pppoe->resume($pppoeId, $actorUserId ?? 1, null, null);
            $result['pppoe'] = method_exists($account, 'toArray') ? $account->toArray() : ['id' => $pppoeId];
        }
        if (!$connectionId && !$pppoeId) {
            throw new \InvalidArgumentException('unsuspend_connection requires connection_id or pppoe_account_id.');
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function updateCustomer(array $config, array $payload, ?int $actorUserId): array
    {
        $customerId = $this->resolveInt($config, $payload, 'customer_id', 'customer_id_path', 'customer_id');
        $actor = $actorUserId ?? 1;
        if (isset($config['status']) && is_string($config['status']) && $config['status'] !== '') {
            $customer = $this->customers->changeStatus($customerId, $config['status'], $actor, null, null);

            return method_exists($customer, 'toArray') ? $customer->toArray() : ['id' => $customerId, 'status' => $config['status']];
        }

        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        if ($fields === []) {
            throw new \InvalidArgumentException('update_customer requires status or fields.');
        }
        $existing = $this->customers->get($customerId);
        $base = method_exists($existing, 'toArray') ? $existing->toArray() : [];
        $merged = array_merge([
            'full_name' => $base['full_name'] ?? $base['name'] ?? '',
            'phone' => $base['phone'] ?? '',
            'address' => $base['address'] ?? '',
            'city' => $base['city'] ?? '',
            'area' => $base['area'] ?? '',
            'email' => $base['email'] ?? null,
            'whatsapp' => $base['whatsapp'] ?? null,
            'father_husband_name' => $base['father_husband_name'] ?? null,
            'cnic' => $base['cnic'] ?? null,
            'notes' => $base['notes'] ?? null,
            'registration_date' => $base['registration_date'] ?? null,
            'installation_date' => $base['installation_date'] ?? null,
            'installation_technician_id' => $base['installation_technician_id'] ?? null,
            'emergency_contact_name' => $base['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $base['emergency_contact_phone'] ?? null,
        ], $fields);
        $customer = $this->customers->update($customerId, UpdateCustomerData::fromArray($merged), $actor, null, null);

        return method_exists($customer, 'toArray') ? $customer->toArray() : ['id' => $customerId, 'updated' => true];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function createTaskPlaceholder(array $config, array $payload): array
    {
        return [
            'placeholder' => true,
            'title' => $this->interpolate((string) ($config['title'] ?? 'Workflow task'), $payload),
            'description' => $this->interpolate((string) ($config['description'] ?? ''), $payload),
            'assignee_id' => isset($config['assignee_id']) ? (int) $config['assignee_id'] : null,
            'source_payload_id' => $payload['id'] ?? null,
            'message' => 'Task module is not implemented; action logged as placeholder.',
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function executeWebhook(array $config, array $payload): array
    {
        $eventKey = (string) ($config['event_key'] ?? $payload['event_key'] ?? $payload['event'] ?? 'workflow.webhook');
        $body = is_array($config['payload'] ?? null) ? $config['payload'] : $payload;
        $body['event_key'] = $eventKey;
        $count = $this->webhooks->dispatch($eventKey, $body);

        return ['event_key' => $eventKey, 'dispatched' => $count];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function callInternalApi(array $config, array $payload, ?int $actorUserId): array
    {
        $handler = (string) ($config['handler'] ?? '');
        $params = is_array($config['params'] ?? null) ? $config['params'] : [];
        $whitelist = [
            'notifications.unread_count' => function () use ($params, $actorUserId): array {
                $userId = (int) ($params['user_id'] ?? $actorUserId ?? 0);
                return ['user_id' => $userId, 'unread_count' => $this->notifications->unreadCount($userId)];
            },
            'connections.get' => function () use ($params, $payload): array {
                $id = (int) ($params['id'] ?? $this->resolve($payload, 'connection_id') ?? 0);
                $connection = $this->connections->get($id);

                return method_exists($connection, 'toArray') ? $connection->toArray() : ['id' => $id];
            },
            'customers.get' => function () use ($params, $payload): array {
                $id = (int) ($params['id'] ?? $this->resolve($payload, 'customer_id') ?? 0);
                $customer = $this->customers->get($id);

                return method_exists($customer, 'toArray') ? $customer->toArray() : ['id' => $id];
            },
        ];

        if (!isset($whitelist[$handler])) {
            throw new \InvalidArgumentException("Internal API handler is not whitelisted: {$handler}");
        }

        return [
            'handler' => $handler,
            'result' => $whitelist[$handler](),
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function previewConfig(string $type, array $config, array $payload): array
    {
        $resolved = $config;
        foreach ($config as $key => $value) {
            if (is_string($key) && str_ends_with($key, '_path') && is_string($value)) {
                $resolved[substr($key, 0, -5)] = $this->resolve($payload, $value);
            }
        }
        $resolved['_action_type'] = $type;

        return $resolved;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     */
    private function resolveInt(array $config, array $payload, string $directKey, string $pathKey, string $fallbackPath): int
    {
        if (isset($config[$directKey]) && $config[$directKey] !== '' && $config[$directKey] !== null) {
            return (int) $config[$directKey];
        }
        $path = (string) ($config[$pathKey] ?? $fallbackPath);
        $value = $this->resolve($payload, $path);
        if ($value === null || $value === '') {
            throw new \InvalidArgumentException("Unable to resolve integer for {$directKey} from path {$path}.");
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $payload
     */
    private function optionalInt(array $config, array $payload, string $directKey, string $pathKey, string $fallbackPath): ?int
    {
        try {
            $value = $this->resolveInt($config, $payload, $directKey, $pathKey, $fallbackPath);

            return $value > 0 ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $payload */
    private function resolve(array $payload, string $path): mixed
    {
        if ($path === '') {
            return null;
        }
        $segments = explode('.', $path);
        $current = $payload;
        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /** @param array<string, mixed> $payload */
    private function interpolate(string $template, array $payload): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function (array $matches) use ($payload): string {
            $value = $this->resolve($payload, $matches[1]);

            return is_scalar($value) || $value === null ? (string) $value : json_encode($value) ?: '';
        }, $template);
    }
}
