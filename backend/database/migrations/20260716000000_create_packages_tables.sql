-- Internet Package Management module schema.
-- Packages are the service-plan/product-catalog boundary described by the architecture.

CREATE TABLE package_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_package_categories_code (code),
    UNIQUE KEY uk_package_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO package_categories (code, name, description) VALUES
('residential', 'Residential', 'Internet packages intended for residential customers.'),
('business', 'Business', 'Internet packages intended for small and medium businesses.'),
('corporate', 'Corporate', 'Managed packages intended for corporate customers.'),
('enterprise', 'Enterprise', 'High-capacity packages intended for enterprise customers.'),
('dedicated', 'Dedicated', 'Dedicated bandwidth internet packages.'),
('custom', 'Custom', 'Custom-configured internet packages.');

CREATE TABLE packages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description TEXT NULL,
    status ENUM('draft','active','inactive','archived') NOT NULL DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_packages_code (code),
    KEY idx_packages_status_deleted (status, deleted_at),
    KEY idx_packages_category_status_deleted (category_id, status, deleted_at),
    KEY idx_packages_name (name),
    KEY idx_packages_created_at (created_at),
    KEY idx_packages_deleted_at (deleted_at),
    CONSTRAINT fk_packages_category FOREIGN KEY (category_id) REFERENCES package_categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_packages_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_packages_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_prices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    package_id BIGINT UNSIGNED NOT NULL,
    billing_period ENUM('monthly','quarterly','semi_annual','annual') NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_package_prices_package_period (package_id, billing_period),
    KEY idx_package_prices_period_amount (billing_period, amount),
    CONSTRAINT fk_package_prices_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_pricing_settings (
    package_id BIGINT UNSIGNED NOT NULL,
    installation_charge DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    supports_tax TINYINT(1) NOT NULL DEFAULT 0,
    supports_discount TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (package_id),
    CONSTRAINT fk_package_pricing_settings_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_bandwidth_profiles (
    package_id BIGINT UNSIGNED NOT NULL,
    download_kbps BIGINT UNSIGNED NOT NULL,
    upload_kbps BIGINT UNSIGNED NOT NULL,
    burst_download_kbps BIGINT UNSIGNED NULL,
    burst_upload_kbps BIGINT UNSIGNED NULL,
    cir_kbps BIGINT UNSIGNED NULL,
    mir_kbps BIGINT UNSIGNED NULL,
    data_limit_bytes BIGINT UNSIGNED NULL,
    is_unlimited TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (package_id),
    KEY idx_package_bandwidth_download (download_kbps),
    CONSTRAINT fk_package_bandwidth_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_network_profiles (
    package_id BIGINT UNSIGNED NOT NULL,
    pppoe_profile_name VARCHAR(150) NULL,
    hotspot_profile_name VARCHAR(150) NULL,
    queue_type VARCHAR(50) NULL,
    vlan_id SMALLINT UNSIGNED NULL,
    ip_pool VARCHAR(150) NULL,
    dns_profile VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (package_id),
    KEY idx_package_network_pppoe (pppoe_profile_name),
    KEY idx_package_network_hotspot (hotspot_profile_name),
    CONSTRAINT fk_package_network_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_customer_rules (
    package_id BIGINT UNSIGNED NOT NULL,
    max_devices SMALLINT UNSIGNED NULL,
    allows_static_ip TINYINT(1) NOT NULL DEFAULT 0,
    allows_public_ip TINYINT(1) NOT NULL DEFAULT 0,
    allows_dynamic_ip TINYINT(1) NOT NULL DEFAULT 1,
    suspension_policy VARCHAR(50) NOT NULL DEFAULT 'grace_period',
    grace_period_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (package_id),
    CONSTRAINT fk_package_customer_rules_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_billing_settings (
    package_id BIGINT UNSIGNED NOT NULL,
    default_billing_cycle ENUM('monthly','quarterly','semi_annual','annual') NOT NULL DEFAULT 'monthly',
    auto_renew TINYINT(1) NOT NULL DEFAULT 1,
    invoice_generation_mode ENUM('advance','arrears','manual') NOT NULL DEFAULT 'advance',
    supports_late_fee TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (package_id),
    CONSTRAINT fk_package_billing_settings_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE package_technical_profiles (
    package_id BIGINT UNSIGNED NOT NULL,
    radius_profile VARCHAR(150) NULL,
    authentication_method VARCHAR(50) NULL,
    qos_profile VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (package_id),
    CONSTRAINT fk_package_technical_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The Customer module intentionally reserved this column for the package catalog.
ALTER TABLE customers
    ADD KEY idx_customers_assigned_package_id (assigned_package_id),
    ADD CONSTRAINT fk_customers_assigned_package FOREIGN KEY (assigned_package_id) REFERENCES packages (id) ON DELETE RESTRICT ON UPDATE CASCADE;
