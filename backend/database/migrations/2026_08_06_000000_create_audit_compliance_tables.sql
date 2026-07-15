-- ============================================================
-- Audit, Compliance & Activity Center — Schema
-- ============================================================

-- Extend existing audit_logs table with new columns
ALTER TABLE audit_logs
  ADD COLUMN module VARCHAR(100) NULL AFTER entity_id,
  ADD COLUMN resource VARCHAR(100) NULL AFTER module,
  ADD COLUMN severity ENUM('info','warning','critical') NOT NULL DEFAULT 'info' AFTER resource,
  ADD COLUMN correlation_id VARCHAR(64) NULL AFTER severity,
  ADD COLUMN url TEXT NULL AFTER user_agent,
  ADD COLUMN compliance_tags JSON NULL AFTER url,
  ADD COLUMN is_immutable TINYINT(1) NOT NULL DEFAULT 0 AFTER compliance_tags,
  ADD INDEX idx_audit_logs_module (module),
  ADD INDEX idx_audit_logs_resource (resource),
  ADD INDEX idx_audit_logs_severity (severity),
  ADD INDEX idx_audit_logs_correlation_id (correlation_id),
  ADD INDEX idx_audit_logs_created_at (created_at);

-- Activity events table (lightweight user activity stream)
CREATE TABLE activity_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    module VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100) NOT NULL,
    resource_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL,
    correlation_id VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_events_user_id (user_id),
    KEY idx_activity_events_module (module),
    KEY idx_activity_events_resource (resource_type, resource_id),
    KEY idx_activity_events_created_at (created_at),
    CONSTRAINT fk_activity_events_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compliance policies table
CREATE TABLE compliance_policies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    policy_type ENUM('data_retention','access_control','immutability','privacy','custom') NOT NULL DEFAULT 'custom',
    rules JSON NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_compliance_policies_type (policy_type),
    KEY idx_compliance_policies_active (is_active),
    CONSTRAINT fk_compliance_policies_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Retention policies table
CREATE TABLE retention_policies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    module VARCHAR(100) NOT NULL DEFAULT '*',
    action_pattern VARCHAR(255) NOT NULL DEFAULT '*',
    retention_days INT UNSIGNED NOT NULL DEFAULT 365,
    auto_archive TINYINT(1) NOT NULL DEFAULT 0,
    archive_location VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_retention_policies_module (module),
    KEY idx_retention_policies_active (is_active),
    CONSTRAINT fk_retention_policies_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit exports table
CREATE TABLE audit_exports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    format ENUM('csv','json') NOT NULL DEFAULT 'csv',
    filters JSON NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    file_path VARCHAR(500) NULL,
    status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_exports_user_id (user_id),
    KEY idx_audit_exports_status (status),
    CONSTRAINT fk_audit_exports_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default retention policies
INSERT INTO retention_policies (name, description, module, action_pattern, retention_days, auto_archive, is_active) VALUES
('Default Audit Log Retention', 'Retain all audit logs for 7 years per compliance requirements', '*', '*', 2555, 0, 1),
('Authentication Logs', 'Retain authentication events for 1 year', 'authentication', 'login%,logout%,password%', 365, 0, 1),
('Financial Audit Trail', 'Retain financial audit events for 7 years', 'billing,payments,finance', '%', 2555, 0, 1),
('Network Configuration Changes', 'Retain network change logs for 3 years', 'mikrotik,pppoe,hotspot,monitoring', '%', 1095, 0, 1),
('Activity Stream', 'Retain user activity stream for 90 days', '*', '%', 90, 1, 1);

-- Seed default compliance policies
INSERT INTO compliance_policies (name, description, policy_type, rules, is_active) VALUES
('Audit Log Immutability', 'All audit logs must be immutable — no UPDATE or DELETE operations permitted', 'immutability', '{"enforce_immutability": true, "prevent_modification": true, "prevent_deletion": true}', 1),
('Data Retention Standard', 'Audit data must be retained per regulatory requirements', 'data_retention', '{"minimum_retention_days": 2555, "financial_retention_days": 2555, "authentication_retention_days": 365}', 1),
('Access Control Policy', 'Only authorized users with audit.view permission may access audit logs', 'access_control', '{"required_permission": "audit.view", "export_permission": "audit.export", "manage_permission": "audit.manage"}', 1);

-- Seed audit & compliance permissions
INSERT INTO permissions (name, description) VALUES
('audit.view', 'View audit logs, activity stream, and audit dashboard'),
('audit.export', 'Export audit logs to CSV or JSON'),
('audit.manage', 'Manage audit configuration and advanced operations'),
('compliance.manage', 'Manage compliance policies and retention rules');
