-- Reports & Business Intelligence: configuration and delivery metadata only.
-- Operational facts remain in their owning module tables.
CREATE TABLE report_templates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL,
    report_key VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    default_filters JSON NULL,
    default_columns JSON NULL,
    visualization JSON NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_report_templates_code (code),
    KEY idx_report_templates_category_status (category, status, deleted_at),
    CONSTRAINT fk_report_templates_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_report_templates_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    report_template_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    report_key VARCHAR(100) NOT NULL,
    filters JSON NULL,
    selected_columns JSON NULL,
    visualization JSON NULL,
    visibility ENUM('private','shared') NOT NULL DEFAULT 'private',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    KEY idx_saved_reports_owner (owner_user_id, deleted_at),
    KEY idx_saved_reports_key (report_key, deleted_at),
    CONSTRAINT fk_saved_reports_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_reports_template FOREIGN KEY (report_template_id) REFERENCES report_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scheduled_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    saved_report_id BIGINT UNSIGNED NOT NULL,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    frequency ENUM('daily','weekly','monthly','custom') NOT NULL,
    schedule_expression VARCHAR(100) NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Karachi',
    export_format ENUM('pdf','xlsx','csv') NOT NULL DEFAULT 'pdf',
    recipients JSON NULL,
    delivery_config JSON NULL,
    status ENUM('draft','active','paused') NOT NULL DEFAULT 'draft',
    next_run_at DATETIME NULL,
    last_run_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    KEY idx_scheduled_reports_owner_status (owner_user_id, status, deleted_at),
    KEY idx_scheduled_reports_next_run (status, next_run_at),
    CONSTRAINT fk_scheduled_reports_saved FOREIGN KEY (saved_report_id) REFERENCES saved_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_scheduled_reports_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE report_export_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    requested_by BIGINT UNSIGNED NOT NULL,
    saved_report_id BIGINT UNSIGNED NULL,
    report_key VARCHAR(100) NOT NULL,
    format ENUM('pdf','xlsx','csv') NOT NULL,
    filters JSON NULL,
    status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
    file_name VARCHAR(255) NULL,
    file_path VARCHAR(500) NULL,
    mime_type VARCHAR(100) NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    error_message VARCHAR(1000) NULL,
    completed_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_report_exports_user_status (requested_by, status, created_at),
    KEY idx_report_exports_expiry (expires_at),
    CONSTRAINT fk_report_exports_user FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_exports_saved FOREIGN KEY (saved_report_id) REFERENCES saved_reports(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, description, created_at, updated_at) VALUES
('reports.view', 'View and generate operational reports.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('reports.export', 'Export reports as PDF, Excel, or CSV.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('reports.manage', 'Manage saved reports, templates, schedules, and export history.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('analytics.view', 'View business intelligence dashboards and analytics.', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = CURRENT_TIMESTAMP;
