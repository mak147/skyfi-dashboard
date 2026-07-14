-- Towers module schema
-- Apply through the project's migration runner; do not alter production manually.

CREATE TABLE towers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pop_site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) NULL,
    tower_type ENUM('lattice', 'monopole', 'guyed', 'building', 'water_tank', 'other') NOT NULL,
    height_meters DECIMAL(6,2) NULL,
    owner ENUM('owned', 'leased', 'shared', 'managed') DEFAULT 'owned',
    address_line1 VARCHAR(500) NULL,
    city VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    gps_latitude DECIMAL(10,7) NULL,
    gps_longitude DECIMAL(10,7) NULL,
    status ENUM('planning', 'active', 'maintenance', 'decommissioned') DEFAULT 'planning',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_towers_code (code),
    KEY idx_towers_pop_site (pop_site_id),
    KEY idx_towers_status (status),
    KEY idx_towers_deleted_at (deleted_at),
    CONSTRAINT fk_towers_pop_site FOREIGN KEY (pop_site_id) REFERENCES pop_sites (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_towers_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_towers_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;