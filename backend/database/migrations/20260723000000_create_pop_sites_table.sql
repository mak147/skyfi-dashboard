-- POP Sites module schema
-- Apply through the project's migration runner; do not alter production manually.

CREATE TABLE pop_sites (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NOT NULL,
    address_line1 VARCHAR(500) NULL,
    address_line2 VARCHAR(500) NULL,
    city VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) DEFAULT 'Pakistan',
    gps_latitude DECIMAL(10,7) NULL,
    gps_longitude DECIMAL(10,7) NULL,
    contact_person VARCHAR(200) NULL,
    contact_phone VARCHAR(20) NULL,
    contact_email VARCHAR(255) NULL,
    power_status ENUM('grid', 'solar', 'generator', 'hybrid', 'unknown') DEFAULT 'unknown',
    fiber_provider VARCHAR(200) NULL,
    status ENUM('planning', 'active', 'maintenance', 'decommissioned') DEFAULT 'planning',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_pop_sites_code (code),
    UNIQUE KEY uk_pop_sites_name (name),
    KEY idx_pop_sites_status (status),
    KEY idx_pop_sites_city (city),
    KEY idx_pop_sites_deleted_at (deleted_at),
    CONSTRAINT fk_pop_sites_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pop_sites_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Permissions
INSERT INTO permissions (name, description) VALUES
('infrastructure.view', 'View POP sites, towers, sectors, and network devices'),
('infrastructure.create', 'Create new infrastructure entities'),
('infrastructure.update', 'Update infrastructure entities'),
('infrastructure.delete', 'Soft delete infrastructure entities'),
('infrastructure.manage', 'Manage infrastructure status and advanced operations');