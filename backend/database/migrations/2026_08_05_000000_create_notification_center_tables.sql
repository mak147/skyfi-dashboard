-- Notification Center module schema.
-- Centralized in-app inbox, templates, user preferences, events, and delivery history.

CREATE TABLE notification_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(100) NOT NULL,
    event_uuid CHAR(36) NOT NULL,
    source_module VARCHAR(60) NOT NULL,
    source_id VARCHAR(64) NULL,
    payload JSON NOT NULL,
    status ENUM('received','processing','processed','failed') NOT NULL DEFAULT 'received',
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_notification_events_uuid (event_uuid),
    KEY idx_notification_events_key_status (event_key, status, created_at),
    KEY idx_notification_events_source (source_module, source_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(180) NOT NULL,
    category VARCHAR(60) NOT NULL,
    channel ENUM('in_app','email','sms','push','webhook') NOT NULL,
    subject_template VARCHAR(500) NULL,
    body_template TEXT NOT NULL,
    locale VARCHAR(20) NOT NULL DEFAULT 'en',
    is_transactional TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    variables JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_notification_templates_code_channel_locale (code, channel, locale),
    KEY idx_notification_templates_category (category, channel, is_active, deleted_at),
    KEY idx_notification_templates_active (is_active, deleted_at),
    KEY idx_notification_templates_deleted (deleted_at),
    CONSTRAINT fk_notification_templates_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_templates_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    recipient_user_id BIGINT UNSIGNED NOT NULL,
    recipient_type VARCHAR(40) NOT NULL DEFAULT 'user',
    notification_type VARCHAR(100) NOT NULL,
    category VARCHAR(60) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    data JSON NULL,
    severity ENUM('info','success','warning','critical') NOT NULL DEFAULT 'info',
    action_url VARCHAR(500) NULL,
    status ENUM('unread','read','archived') NOT NULL DEFAULT 'unread',
    read_at TIMESTAMP NULL,
    source_module VARCHAR(60) NULL,
    source_event VARCHAR(100) NULL,
    source_id VARCHAR(64) NULL,
    event_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_notifications_uuid (uuid),
    KEY idx_notifications_recipient_status (recipient_user_id, status, created_at),
    KEY idx_notifications_type (notification_type),
    KEY idx_notifications_category (category, created_at),
    KEY idx_notifications_source (source_module, source_event, source_id),
    KEY idx_notifications_event (event_id),
    CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_event FOREIGN KEY (event_id) REFERENCES notification_events(id) ON DELETE SET NULL,
    CONSTRAINT fk_notifications_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_notification_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('in_app','email','sms','push','webhook') NOT NULL,
    category VARCHAR(60) NOT NULL DEFAULT '*',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    quiet_hours_timezone VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_notification_prefs (user_id, channel, category),
    KEY idx_user_notification_prefs_user (user_id, is_enabled),
    CONSTRAINT fk_user_notification_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_deliveries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NULL,
    event_id BIGINT UNSIGNED NULL,
    recipient_user_id BIGINT UNSIGNED NULL,
    channel ENUM('in_app','email','sms','push','webhook') NOT NULL,
    template_id BIGINT UNSIGNED NULL,
    status ENUM('pending','queued','sent','failed','skipped') NOT NULL DEFAULT 'pending',
    provider VARCHAR(60) NULL,
    provider_message_id VARCHAR(120) NULL,
    subject VARCHAR(500) NULL,
    body TEXT NULL,
    fail_reason TEXT NULL,
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_notification_deliveries_recipient (recipient_user_id, channel, status, created_at),
    KEY idx_notification_deliveries_status (status, created_at),
    KEY idx_notification_deliveries_notification (notification_id),
    KEY idx_notification_deliveries_event (event_id),
    CONSTRAINT fk_notification_deliveries_notification FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_deliveries_event FOREIGN KEY (event_id) REFERENCES notification_events(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_deliveries_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_notification_deliveries_template FOREIGN KEY (template_id) REFERENCES notification_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default templates (English)
INSERT INTO notification_templates (code, name, category, channel, subject_template, body_template, locale, is_transactional, is_active, variables) VALUES
('invoice.generated', 'Invoice Generated', 'billing', 'in_app', 'Invoice {{invoice_number}} generated', 'Invoice {{invoice_number}} for {{amount}} has been generated.', 'en', 0, 1, JSON_ARRAY('invoice_number','amount','customer_name')),
('invoice.generated', 'Invoice Generated Email', 'billing', 'email', 'Your SkyFi invoice {{invoice_number}}', 'Hello,\n\nInvoice {{invoice_number}} for {{amount}} has been generated.\n\nThank you,\nSkyFi Networks', 'en', 0, 1, JSON_ARRAY('invoice_number','amount','customer_name')),
('payment.received', 'Payment Received', 'payments', 'in_app', 'Payment {{payment_number}} received', 'Payment {{payment_number}} of {{amount}} was received successfully.', 'en', 0, 1, JSON_ARRAY('payment_number','amount')),
('payment.received', 'Payment Received Email', 'payments', 'email', 'Payment confirmation {{payment_number}}', 'Payment {{payment_number}} of {{amount}} was received. Thank you.', 'en', 0, 1, JSON_ARRAY('payment_number','amount')),
('payment.failed', 'Payment Failed', 'payments', 'in_app', 'Payment failed', 'A payment attempt failed: {{reason}}.', 'en', 0, 1, JSON_ARRAY('reason','amount')),
('payment.failed', 'Payment Failed Email', 'payments', 'email', 'Payment failed', 'A payment attempt of {{amount}} failed. Reason: {{reason}}.', 'en', 0, 1, JSON_ARRAY('reason','amount')),
('payment.reversed', 'Payment Reversed', 'payments', 'in_app', 'Payment {{payment_number}} reversed', 'Payment {{payment_number}} was reversed.', 'en', 0, 1, JSON_ARRAY('payment_number','amount')),
('support.ticket.created', 'New Support Ticket', 'support', 'in_app', 'Ticket {{ticket_number}} created', 'New support ticket {{ticket_number}}: {{subject}}', 'en', 0, 1, JSON_ARRAY('ticket_number','subject','priority')),
('support.ticket.assigned', 'Ticket Assigned', 'support', 'in_app', 'Ticket {{ticket_number}} assigned to you', 'You have been assigned ticket {{ticket_number}}: {{subject}}', 'en', 0, 1, JSON_ARRAY('ticket_number','subject','priority')),
('support.ticket.assigned', 'Ticket Assigned Email', 'support', 'email', 'Assigned: {{ticket_number}}', 'You have been assigned support ticket {{ticket_number}} ({{priority}}): {{subject}}', 'en', 0, 1, JSON_ARRAY('ticket_number','subject','priority')),
('support.ticket.resolved', 'Ticket Resolved', 'support', 'in_app', 'Ticket {{ticket_number}} resolved', 'Support ticket {{ticket_number}} has been resolved.', 'en', 0, 1, JSON_ARRAY('ticket_number','subject')),
('support.ticket.replied', 'Ticket Reply', 'support', 'in_app', 'Reply on ticket {{ticket_number}}', 'A new reply was added to ticket {{ticket_number}}.', 'en', 0, 1, JSON_ARRAY('ticket_number')),
('monitoring.router_offline', 'Router Offline', 'network', 'in_app', 'Router offline: {{title}}', '{{description}}', 'en', 1, 1, JSON_ARRAY('title','description','device_id')),
('monitoring.router_offline', 'Router Offline Email', 'network', 'email', 'CRITICAL: Router offline', '{{title}}\n\n{{description}}', 'en', 1, 1, JSON_ARRAY('title','description','device_id')),
('monitoring.high_cpu', 'High CPU Alert', 'network', 'in_app', 'High CPU: {{title}}', '{{description}}', 'en', 1, 1, JSON_ARRAY('title','description','metric_value')),
('field.installation.scheduled', 'Installation Scheduled', 'field', 'in_app', 'Installation scheduled', 'Installation for {{customer_name}} is scheduled for {{scheduled_at}}.', 'en', 0, 1, JSON_ARRAY('customer_name','scheduled_at')),
('field.installation.completed', 'Installation Completed', 'field', 'in_app', 'Installation completed', 'Field work {{work_order_number}} has been completed.', 'en', 0, 1, JSON_ARRAY('work_order_number','customer_id')),
('inventory.low_stock', 'Low Inventory', 'inventory', 'in_app', 'Low stock: {{product_name}}', 'Product {{sku}} ({{product_name}}) is at or below reorder level ({{quantity}}).', 'en', 0, 1, JSON_ARRAY('product_name','sku','quantity','reorder_level')),
('inventory.low_stock', 'Low Inventory Email', 'inventory', 'email', 'Low stock alert: {{product_name}}', 'Product {{sku}} is low on stock. Quantity: {{quantity}}, reorder level: {{reorder_level}}.', 'en', 0, 1, JSON_ARRAY('product_name','sku','quantity','reorder_level')),
('purchasing.order.approved', 'Purchase Order Approved', 'purchasing', 'in_app', 'PO {{order_number}} approved', 'Purchase order {{order_number}} has been approved.', 'en', 0, 1, JSON_ARRAY('order_number','total_amount')),
('purchasing.request.approved', 'Purchase Request Approved', 'purchasing', 'in_app', 'PR {{request_number}} approved', 'Purchase request {{request_number}} has been approved.', 'en', 0, 1, JSON_ARRAY('request_number')),
('vendor.contract.expiring', 'Supplier Contract Expiring', 'vendors', 'in_app', 'Contract {{contract_number}} expiring', 'Supplier contract {{contract_number}} for {{supplier_name}} expires in {{days_remaining}} days.', 'en', 0, 1, JSON_ARRAY('contract_number','supplier_name','days_remaining')),
('vendor.contract.expiring', 'Supplier Contract Expiring Email', 'vendors', 'email', 'Contract expiring: {{contract_number}}', 'Contract {{contract_number}} with {{supplier_name}} expires in {{days_remaining}} days.', 'en', 0, 1, JSON_ARRAY('contract_number','supplier_name','days_remaining')),
('connection.approved', 'Connection Approved', 'system', 'in_app', 'Connection approved', 'Connection #{{connection_id}} has been approved and installation may proceed.', 'en', 0, 1, JSON_ARRAY('connection_id','customer_id'));

INSERT INTO permissions (name, description, created_at, updated_at) VALUES
('notifications.view', 'View notification center inbox and unread counts.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('notifications.manage', 'Manage delivery history, events, and manual notification dispatch.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('notifications.templates', 'Manage notification templates across channels.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('notifications.preferences', 'Manage personal notification delivery preferences.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = CURRENT_TIMESTAMP;

INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name IN (
  'Super Administrator','Company Owner','Regional Manager','Finance Department',
  'Sales Team','Customer Support','Installation Team / Field Technician',
  'Network Engineer','Inventory Manager'
)
WHERE p.name IN ('notifications.view','notifications.preferences');

INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
JOIN roles r ON r.name IN ('Super Administrator','Company Owner','Regional Manager')
WHERE p.name IN ('notifications.manage','notifications.templates');
