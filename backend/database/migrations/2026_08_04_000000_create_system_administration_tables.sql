-- Company & System Administration module schema.
CREATE TABLE companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(180) NOT NULL,
    legal_name VARCHAR(220) NULL,
    registration_number VARCHAR(100) NULL,
    tax_number VARCHAR(100) NULL,
    address_line_1 VARCHAR(255) NULL,
    address_line_2 VARCHAR(255) NULL,
    city VARCHAR(120) NULL,
    state VARCHAR(120) NULL,
    postal_code VARCHAR(40) NULL,
    country VARCHAR(120) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    logo_path VARCHAR(500) NULL,
    favicon_path VARCHAR(500) NULL,
    timezone VARCHAR(100) NOT NULL DEFAULT 'Asia/Karachi',
    currency_code CHAR(3) NOT NULL DEFAULT 'PKR',
    date_format VARCHAR(40) NOT NULL DEFAULT 'YYYY-MM-DD',
    language_code VARCHAR(20) NOT NULL DEFAULT 'en',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_companies_status (status), KEY idx_companies_currency (currency_code), KEY idx_companies_language (language_code),
    CONSTRAINT fk_companies_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE branches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL, name VARCHAR(180) NOT NULL, description VARCHAR(500) NULL,
    address_line_1 VARCHAR(255) NULL, address_line_2 VARCHAR(255) NULL, city VARCHAR(120) NULL, state VARCHAR(120) NULL, postal_code VARCHAR(40) NULL, country VARCHAR(120) NULL,
    phone VARCHAR(40) NULL, email VARCHAR(255) NULL, manager_user_id BIGINT UNSIGNED NULL, timezone VARCHAR(100) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_branches_code (code), KEY idx_branches_name (name), KEY idx_branches_city_status (city,status,deleted_at), KEY idx_branches_status (status,deleted_at), KEY idx_branches_manager (manager_user_id),
    CONSTRAINT fk_branches_manager FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_branches_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_branches_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NULL, code VARCHAR(50) NOT NULL, name VARCHAR(180) NOT NULL, description VARCHAR(500) NULL, head_user_id BIGINT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active', created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_departments_code (code), KEY idx_departments_branch_status (branch_id,status,deleted_at), KEY idx_departments_name (name), KEY idx_departments_status (status,deleted_at), KEY idx_departments_head (head_user_id),
    CONSTRAINT fk_departments_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    CONSTRAINT fk_departments_head FOREIGN KEY (head_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_departments_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_departments_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    application_name VARCHAR(180) NOT NULL DEFAULT 'SkyFi ISP Management', environment_name VARCHAR(80) NOT NULL DEFAULT 'production', maintenance_mode TINYINT(1) NOT NULL DEFAULT 0, maintenance_message VARCHAR(500) NULL, session_timeout_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    password_policy JSON NOT NULL, mfa_policy JSON NULL, file_upload_limits JSON NOT NULL, email_settings JSON NULL, sms_provider_settings JSON NULL, cache_settings JSON NULL, logging_settings JSON NULL,
    created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_system_settings_environment (environment_name), KEY idx_system_settings_maintenance (maintenance_mode),
    CONSTRAINT fk_system_settings_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_system_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE branding_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    theme VARCHAR(50) NOT NULL DEFAULT 'system', primary_color VARCHAR(20) NOT NULL DEFAULT '#4f46e5', secondary_color VARCHAR(20) NOT NULL DEFAULT '#10b981', logo_path VARCHAR(500) NULL, favicon_path VARCHAR(500) NULL, login_background_path VARCHAR(500) NULL, login_headline VARCHAR(180) NULL, login_subheadline VARCHAR(300) NULL, footer_text VARCHAR(255) NULL, custom_css JSON NULL,
    created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branding_theme (theme), CONSTRAINT fk_branding_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL, CONSTRAINT fk_branding_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE localization_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    default_language VARCHAR(20) NOT NULL DEFAULT 'en', supported_languages JSON NOT NULL, default_timezone VARCHAR(100) NOT NULL DEFAULT 'Asia/Karachi', supported_timezones JSON NOT NULL, default_currency CHAR(3) NOT NULL DEFAULT 'PKR', supported_currencies JSON NOT NULL, date_format VARCHAR(40) NOT NULL DEFAULT 'YYYY-MM-DD', time_format VARCHAR(40) NOT NULL DEFAULT 'HH:mm', number_format JSON NOT NULL, first_day_of_week TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_localization_language (default_language), KEY idx_localization_currency (default_currency), KEY idx_localization_timezone (default_timezone), CONSTRAINT fk_localization_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL, CONSTRAINT fk_localization_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email_enabled TINYINT(1) NOT NULL DEFAULT 1, sms_enabled TINYINT(1) NOT NULL DEFAULT 0, in_app_enabled TINYINT(1) NOT NULL DEFAULT 1, alert_preferences JSON NOT NULL, reminder_preferences JSON NOT NULL, email_templates_enabled TINYINT(1) NOT NULL DEFAULT 1, sms_provider_placeholder JSON NULL,
    created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_notification_channels (email_enabled,sms_enabled,in_app_enabled), CONSTRAINT fk_notification_preferences_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL, CONSTRAINT fk_notification_preferences_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO companies (company_name, timezone, currency_code, date_format, language_code) VALUES ('SkyFi Networks', 'Asia/Karachi', 'PKR', 'YYYY-MM-DD', 'en');
INSERT INTO system_settings (application_name, environment_name, password_policy, file_upload_limits, mfa_policy, email_settings, sms_provider_settings, cache_settings, logging_settings) VALUES ('SkyFi ISP Management', 'production', JSON_OBJECT('min_length',12,'require_uppercase',true,'require_lowercase',true,'require_number',true,'require_symbol',true), JSON_OBJECT('max_mb',5,'allowed_mime_types',JSON_ARRAY('image/png','image/jpeg','image/webp','image/x-icon')), JSON_OBJECT('enabled',false,'status','placeholder'), JSON_OBJECT('enabled',false,'provider','smtp'), JSON_OBJECT('enabled',false,'status','placeholder'), JSON_OBJECT('driver','file','ttl_seconds',300), JSON_OBJECT('level','info'));
INSERT INTO branding_settings (theme, primary_color, secondary_color, footer_text, custom_css) VALUES ('system', '#4f46e5', '#10b981', 'SkyFi Networks ISP Management', JSON_OBJECT());
INSERT INTO localization_settings (default_language, supported_languages, default_timezone, supported_timezones, default_currency, supported_currencies, date_format, time_format, number_format) VALUES ('en', JSON_ARRAY('en'), 'Asia/Karachi', JSON_ARRAY('Asia/Karachi','UTC'), 'PKR', JSON_ARRAY('PKR'), 'YYYY-MM-DD', 'HH:mm', JSON_OBJECT('decimal_separator','.','thousand_separator',',','precision',2));
INSERT INTO notification_preferences (alert_preferences, reminder_preferences, sms_provider_placeholder) VALUES (JSON_OBJECT('network',true,'billing',true,'support',true), JSON_OBJECT('invoice_due',true,'ticket_follow_up',true), JSON_OBJECT('enabled',false,'status','placeholder'));
INSERT INTO permissions (name, description, created_at, updated_at) VALUES ('system.view','View company and system administration settings.',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),('system.update','Update company, branding, localization, notification, and operational settings.',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),('system.manage','Manage branches, departments, maintenance toggles, and administrative operations.',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE description=VALUES(description), updated_at=CURRENT_TIMESTAMP;
