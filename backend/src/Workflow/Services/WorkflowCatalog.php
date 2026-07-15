<?php

declare(strict_types=1);

namespace SkyFi\Workflow\Services;

final class WorkflowCatalog
{
    /** @return list<array<string, mixed>> */
    public function operators(): array
    {
        return [
            ['id' => 'equals', 'label' => 'Equals', 'value_type' => 'any'],
            ['id' => 'not_equals', 'label' => 'Not Equals', 'value_type' => 'any'],
            ['id' => 'contains', 'label' => 'Contains', 'value_type' => 'string'],
            ['id' => 'starts_with', 'label' => 'Starts With', 'value_type' => 'string'],
            ['id' => 'ends_with', 'label' => 'Ends With', 'value_type' => 'string'],
            ['id' => 'greater_than', 'label' => 'Greater Than', 'value_type' => 'number'],
            ['id' => 'less_than', 'label' => 'Less Than', 'value_type' => 'number'],
            ['id' => 'between', 'label' => 'Between', 'value_type' => 'range'],
            ['id' => 'is_empty', 'label' => 'Is Empty', 'value_type' => 'none'],
            ['id' => 'is_not_empty', 'label' => 'Is Not Empty', 'value_type' => 'none'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function actions(): array
    {
        return [
            [
                'type' => 'create_notification',
                'label' => 'Create Notification',
                'module' => 'notifications',
                'description' => 'Dispatch an in-app notification via Notification Center.',
                'config_schema' => [
                    'type' => ['type' => 'string', 'required' => true],
                    'recipient_user_ids' => ['type' => 'array', 'required' => false],
                    'channels' => ['type' => 'array', 'required' => false],
                    'severity' => ['type' => 'string', 'required' => false],
                    'data' => ['type' => 'object', 'required' => false],
                ],
            ],
            [
                'type' => 'send_email',
                'label' => 'Send Email',
                'module' => 'notifications',
                'description' => 'Send an email notification through Notification Center.',
                'config_schema' => [
                    'type' => ['type' => 'string', 'required' => true],
                    'recipient_user_ids' => ['type' => 'array', 'required' => false],
                    'data' => ['type' => 'object', 'required' => false],
                ],
            ],
            [
                'type' => 'send_sms',
                'label' => 'Send SMS (Placeholder)',
                'module' => 'notifications',
                'description' => 'Queue an SMS via Notification Center SMS channel placeholder.',
                'config_schema' => [
                    'type' => ['type' => 'string', 'required' => true],
                    'recipient_user_ids' => ['type' => 'array', 'required' => false],
                    'data' => ['type' => 'object', 'required' => false],
                ],
            ],
            [
                'type' => 'create_support_ticket',
                'label' => 'Create Support Ticket',
                'module' => 'support',
                'description' => 'Create a support ticket using Support Ticket Service.',
                'config_schema' => [
                    'customer_id_path' => ['type' => 'string', 'required' => false],
                    'customer_id' => ['type' => 'integer', 'required' => false],
                    'category_id' => ['type' => 'integer', 'required' => true],
                    'priority' => ['type' => 'string', 'required' => false],
                    'subject' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => true],
                ],
            ],
            [
                'type' => 'assign_technician',
                'label' => 'Assign Technician',
                'module' => 'field-service',
                'description' => 'Assign a technician to a work order or support ticket.',
                'config_schema' => [
                    'target' => ['type' => 'string', 'required' => true],
                    'work_order_id_path' => ['type' => 'string', 'required' => false],
                    'ticket_id_path' => ['type' => 'string', 'required' => false],
                    'technician_id' => ['type' => 'integer', 'required' => false],
                    'technician_id_path' => ['type' => 'string', 'required' => false],
                ],
            ],
            [
                'type' => 'generate_invoice',
                'label' => 'Generate Invoice',
                'module' => 'billing',
                'description' => 'Generate an invoice through Billing Invoice Service.',
                'config_schema' => [
                    'connection_id_path' => ['type' => 'string', 'required' => false],
                    'connection_id' => ['type' => 'integer', 'required' => false],
                    'customer_id_path' => ['type' => 'string', 'required' => false],
                    'billing_period_start' => ['type' => 'string', 'required' => false],
                    'billing_period_end' => ['type' => 'string', 'required' => false],
                ],
            ],
            [
                'type' => 'activate_connection',
                'label' => 'Activate Connection',
                'module' => 'connections',
                'description' => 'Activate a customer connection.',
                'config_schema' => [
                    'connection_id_path' => ['type' => 'string', 'required' => false],
                    'connection_id' => ['type' => 'integer', 'required' => false],
                ],
            ],
            [
                'type' => 'suspend_connection',
                'label' => 'Suspend Connection',
                'module' => 'connections',
                'description' => 'Suspend a connection and related PPPoE account when available.',
                'config_schema' => [
                    'connection_id_path' => ['type' => 'string', 'required' => false],
                    'connection_id' => ['type' => 'integer', 'required' => false],
                    'pppoe_account_id_path' => ['type' => 'string', 'required' => false],
                    'pppoe_account_id' => ['type' => 'integer', 'required' => false],
                ],
            ],
            [
                'type' => 'unsuspend_connection',
                'label' => 'Unsuspend Connection',
                'module' => 'connections',
                'description' => 'Unsuspend a connection and resume related PPPoE account when available.',
                'config_schema' => [
                    'connection_id_path' => ['type' => 'string', 'required' => false],
                    'connection_id' => ['type' => 'integer', 'required' => false],
                    'pppoe_account_id_path' => ['type' => 'string', 'required' => false],
                    'pppoe_account_id' => ['type' => 'integer', 'required' => false],
                ],
            ],
            [
                'type' => 'update_customer',
                'label' => 'Update Customer',
                'module' => 'customers',
                'description' => 'Update customer fields or status via Customer Service.',
                'config_schema' => [
                    'customer_id_path' => ['type' => 'string', 'required' => false],
                    'customer_id' => ['type' => 'integer', 'required' => false],
                    'status' => ['type' => 'string', 'required' => false],
                    'fields' => ['type' => 'object', 'required' => false],
                ],
            ],
            [
                'type' => 'create_task',
                'label' => 'Create Task (Placeholder)',
                'module' => 'workflow',
                'description' => 'Placeholder task creation until a dedicated task module exists.',
                'config_schema' => [
                    'title' => ['type' => 'string', 'required' => true],
                    'description' => ['type' => 'string', 'required' => false],
                    'assignee_id' => ['type' => 'integer', 'required' => false],
                ],
            ],
            [
                'type' => 'execute_webhook',
                'label' => 'Execute Webhook',
                'module' => 'integration',
                'description' => 'Dispatch payload to outbound webhooks via Integration WebhookDispatcher.',
                'config_schema' => [
                    'event_key' => ['type' => 'string', 'required' => true],
                    'payload' => ['type' => 'object', 'required' => false],
                ],
            ],
            [
                'type' => 'call_internal_api',
                'label' => 'Call Internal API',
                'module' => 'workflow',
                'description' => 'Call a whitelisted internal service handler.',
                'config_schema' => [
                    'handler' => ['type' => 'string', 'required' => true],
                    'params' => ['type' => 'object', 'required' => false],
                ],
            ],
        ];
    }

    /** @return list<string> */
    public function scheduleModes(): array
    {
        return ['immediate', 'delayed', 'cron', 'recurring'];
    }

    /** @return list<string> */
    public function statuses(): array
    {
        return ['draft', 'active', 'paused', 'disabled'];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'operators' => $this->operators(),
            'actions' => $this->actions(),
            'schedule_modes' => $this->scheduleModes(),
            'statuses' => $this->statuses(),
            'group_logic' => ['AND', 'OR'],
        ];
    }
}
