-- Client Applications
CREATE TABLE client_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    redirect_uris JSON NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    rate_limit_per_minute INT UNSIGNED NOT NULL DEFAULT 60,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys
CREATE TABLE api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_application_id INT UNSIGNED NULL,
    name VARCHAR(180) NOT NULL,
    key_prefix VARCHAR(12) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    scopes JSON NOT NULL,
    ip_allow_list JSON NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    rate_limit_per_minute INT UNSIGNED NULL DEFAULT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_keys_client_application FOREIGN KEY (client_application_id) REFERENCES client_applications(id) ON DELETE SET NULL,
    INDEX idx_api_keys_key_hash (key_hash),
    INDEX idx_api_keys_client (client_application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event Registry
CREATE TABLE event_registry (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(180) NOT NULL UNIQUE,
    source_module VARCHAR(80) NOT NULL,
    description TEXT NULL,
    payload_schema JSON NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_registry_key (event_key),
    INDEX idx_event_registry_source (source_module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks
CREATE TABLE webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_application_id INT UNSIGNED NULL,
    name VARCHAR(180) NOT NULL,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(255) NOT NULL,
    events JSON NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_inbound BOOLEAN NOT NULL DEFAULT FALSE,
    inbound_secret VARCHAR(255) NULL,
    retry_policy JSON NOT NULL,
    filter_rules JSON NULL,
    content_type VARCHAR(50) NOT NULL DEFAULT 'application/json',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_webhooks_client_application FOREIGN KEY (client_application_id) REFERENCES client_applications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook Deliveries
CREATE TABLE webhook_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_id BIGINT UNSIGNED NOT NULL,
    event_id INT UNSIGNED NULL,
    event_key VARCHAR(180) NOT NULL,
    payload JSON NOT NULL,
    request_headers JSON NULL,
    response_status_code INT UNSIGNED NULL,
    response_body TEXT NULL,
    response_headers JSON NULL,
    attempt_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('pending','sent','failed','retrying') NOT NULL DEFAULT 'pending',
    next_retry_at TIMESTAMP NULL,
    error_message TEXT NULL,
    duration_ms INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_webhook_deliveries_webhook FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE,
    CONSTRAINT fk_webhook_deliveries_event FOREIGN KEY (event_id) REFERENCES event_registry(id) ON DELETE SET NULL,
    INDEX idx_webhook_deliveries_webhook (webhook_id),
    INDEX idx_webhook_deliveries_event_key (event_key),
    INDEX idx_webhook_deliveries_status (status, next_retry_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Connector Configurations
CREATE TABLE connector_configurations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    connector_type VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    config JSON NOT NULL,
    is_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    rate_limit_per_minute INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Request Logs
CREATE TABLE api_request_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id BIGINT UNSIGNED NULL,
    client_application_id INT UNSIGNED NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(500) NOT NULL,
    status_code INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    request_headers JSON NULL,
    request_body JSON NULL,
    response_body JSON NULL,
    duration_ms INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_request_logs_key (api_key_id, created_at),
    INDEX idx_api_request_logs_path (path, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Integration Events
INSERT INTO event_registry (event_key, source_module, description, payload_schema) VALUES
('customer.created', 'customers', 'A new customer record was created.', '{"type":"object","properties":{"id":{"type":"integer"},"name":{"type":"string"},"email":{"type":"string"},"status":{"type":"string"}}}'),
('customer.updated', 'customers', 'Customer details were updated.', '{"type":"object","properties":{"id":{"type":"integer"},"changes":{"type":"object"}}}'),
('customer.deleted', 'customers', 'A customer record was soft-deleted.', '{"type":"object","properties":{"id":{"type":"integer"}}}'),
('customer.status_changed', 'customers', 'Customer status was changed.', '{"type":"object","properties":{"id":{"type":"integer"},"old_status":{"type":"string"},"new_status":{"type":"string"}}}'),
('invoice.generated', 'billing', 'An invoice was generated.', '{"type":"object","properties":{"id":{"type":"integer"},"invoice_number":{"type":"string"},"total_amount":{"type":"number"},"customer_id":{"type":"integer"}}}'),
('invoice.updated', 'billing', 'An invoice was updated.', '{"type":"object","properties":{"id":{"type":"integer"},"changes":{"type":"object"}}}'),
('invoice.paid', 'billing', 'An invoice was fully paid.', '{"type":"object","properties":{"id":{"type":"integer"},"invoice_number":{"type":"string"},"total_amount":{"type":"number"}}}'),
('invoice.overdue', 'billing', 'An invoice became overdue.', '{"type":"object","properties":{"id":{"type":"integer"},"invoice_number":{"type":"string"},"due_date":{"type":"string"}}}'),
('invoice.voided', 'billing', 'An invoice was voided.', '{"type":"object","properties":{"id":{"type":"integer"}}}'),
('payment.completed', 'payments', 'A payment was completed.', '{"type":"object","properties":{"id":{"type":"integer"},"amount":{"type":"number"},"payment_method":{"type":"string"},"customer_id":{"type":"integer"}}}'),
('payment.failed', 'payments', 'A payment attempt failed.', '{"type":"object","properties":{"id":{"type":"integer"},"amount":{"type":"number"},"reason":{"type":"string"}}}'),
('payment.reversed', 'payments', 'A payment was reversed.', '{"type":"object","properties":{"id":{"type":"integer"},"amount":{"type":"number"}}}'),
('payment.refunded', 'payments', 'A payment refund was issued.', '{"type":"object","properties":{"id":{"type":"integer"},"refund_amount":{"type":"number"}}}'),
('journal_entry.created', 'finance', 'A journal entry was created.', '{"type":"object","properties":{"id":{"type":"integer"},"description":{"type":"string"}}}'),
('journal_entry.posted', 'finance', 'A journal entry was posted.', '{"type":"object","properties":{"id":{"type":"integer"}}}'),
('inventory.low_stock', 'inventory', 'Stock level fell below reorder point.', '{"type":"object","properties":{"product_id":{"type":"integer"},"product_name":{"type":"string"},"quantity":{"type":"integer"}}}'),
('inventory.stock_adjusted', 'inventory', 'An inventory stock adjustment was made.', '{"type":"object","properties":{"id":{"type":"integer"},"product_id":{"type":"integer"},"adjustment":{"type":"integer"}}}'),
('inventory.transfer.completed', 'inventory', 'An inventory transfer was completed.', '{"type":"object","properties":{"id":{"type":"integer"}}}'),
('purchasing.request.approved', 'purchasing', 'A purchase request was approved.', '{"type":"object","properties":{"id":{"type":"integer"},"request_number":{"type":"string"}}}'),
('purchasing.order.approved', 'purchasing', 'A purchase order was approved.', '{"type":"object","properties":{"id":{"type":"integer"},"order_number":{"type":"string"}}}'),
('purchasing.order.received', 'purchasing', 'A purchase order was received.', '{"type":"object","properties":{"id":{"type":"integer"}}}'),
('vendor.contract.expiring', 'vendors', 'A vendor contract is approaching expiry.', '{"type":"object","properties":{"id":{"type":"integer"},"contract_number":{"type":"string"},"days_remaining":{"type":"integer"}}}'),
('vendor.created', 'vendors', 'A new vendor was created.', '{"type":"object","properties":{"id":{"type":"integer"},"name":{"type":"string"}}}'),
('support.ticket.created', 'support', 'A support ticket was created.', '{"type":"object","properties":{"id":{"type":"integer"},"subject":{"type":"string"},"priority":{"type":"string"}}}'),
('support.ticket.assigned', 'support', 'A support ticket was assigned.', '{"type":"object","properties":{"id":{"type":"integer"},"assigned_to":{"type":"integer"}}}'),
('support.ticket.resolved', 'support', 'A support ticket was resolved.', '{"type":"object","properties":{"id":{"type":"integer"}}}'),
('monitoring.alert.triggered', 'monitoring', 'A monitoring alert was triggered.', '{"type":"object","properties":{"alert_id":{"type":"integer"},"device_id":{"type":"integer"},"metric":{"type":"string"}}}'),
('monitoring.device.offline', 'monitoring', 'A network device went offline.', '{"type":"object","properties":{"device_id":{"type":"integer"},"device_name":{"type":"string"}}}'),
('monitoring.device.recovered', 'monitoring', 'A network device came back online.', '{"type":"object","properties":{"device_id":{"type":"integer"},"device_name":{"type":"string"}}}'),
('pppoe.account.created', 'pppoe', 'A PPPoE account was created.', '{"type":"object","properties":{"id":{"type":"integer"},"username":{"type":"string"}}}'),
('pppoe.account.disabled', 'pppoe', 'A PPPoE account was disabled.', '{"type":"object","properties":{"id":{"type":"integer"},"username":{"type":"string"}}}'),
('pppoe.session.connected', 'pppoe', 'A PPPoE session was established.', '{"type":"object","properties":{"username":{"type":"string"},"ip_address":{"type":"string"}}}'),
('hotspot.user.created', 'hotspot', 'A hotspot user was created.', '{"type":"object","properties":{"id":{"type":"integer"},"username":{"type":"string"}}}'),
('hotspot.session.started', 'hotspot', 'A hotspot session started.', '{"type":"object","properties":{"username":{"type":"string"},"mac_address":{"type":"string"}}}'),
('field.installation.completed', 'field-service', 'A field installation was completed.', '{"type":"object","properties":{"id":{"type":"integer"},"customer_id":{"type":"integer"}}}'),
('field.work_order.completed', 'field-service', 'A work order was completed.', '{"type":"object","properties":{"id":{"type":"integer"},"work_order_number":{"type":"string"}}}'),
('notification.dispatched', 'notifications', 'A notification was dispatched.', '{"type":"object","properties":{"id":{"type":"integer"},"type":{"type":"string"},"channels":{"type":"array"}}}'),
('notification.delivery.failed', 'notifications', 'A notification delivery failed.', '{"type":"object","properties":{"id":{"type":"integer"},"channel":{"type":"string"},"reason":{"type":"string"}}}');

-- Seed Connector Placeholders
INSERT INTO connector_configurations (connector_type, name, description, config, is_enabled) VALUES
('stripe', 'Stripe', 'Stripe payment processing gateway.', '{"api_key":"","webhook_secret":"","test_mode":true}', FALSE),
('jazzcash', 'JazzCash', 'JazzCash mobile wallet payment gateway.', '{"merchant_id":"","password":"","integrity_salt":"","api_url":""}', FALSE),
('easypaisa', 'Easypaisa', 'Easypaisa mobile wallet payment gateway.', '{"merchant_id":"","store_id":"","hash_key":"","api_url":""}', FALSE),
('whatsapp', 'WhatsApp Business', 'WhatsApp Business API for notifications.', '{"api_url":"","phone_number_id":"","access_token":"","verify_token":""}', FALSE),
('email', 'Email Provider', 'SMTP or API-based email delivery.', '{"host":"","port":587,"username":"","password":"","from_address":"","from_name":""}', FALSE),
('sms', 'SMS Provider', 'SMS gateway for text notifications.', '{"provider":"","api_url":"","api_key":"","sender_id":""}', FALSE),
('google_maps', 'Google Maps', 'Google Maps API for geocoding and mapping.', '{"api_key":"","geocoding_enabled":true}', FALSE),
('slack', 'Slack', 'Slack workspace integration for alerts and notifications.', '{"webhook_url":"","channel":"","bot_name":""}', FALSE),
('discord', 'Discord', 'Discord webhook integration for alerts.', '{"webhook_url":"","username":"","avatar_url":""}', FALSE);

-- Integration Permissions
INSERT INTO permissions (name, description) VALUES
('integration.view', 'View integration dashboard, events, and connector configurations.'),
('integration.manage', 'Manage client applications, connectors, and request logs.'),
('integration.webhooks', 'Manage webhooks, delivery history, and retry operations.'),
('integration.apikeys', 'Manage API keys, scopes, and key regeneration.');

-- Assign to Admin Role (assuming role ID 1 is Super Administrator)
INSERT INTO permission_role (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name LIKE 'integration.%';
