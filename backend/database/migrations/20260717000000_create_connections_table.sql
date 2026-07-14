-- Connection Management module schema
-- Apply through the project's migration runner; do not alter production manually.

CREATE TABLE connections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    connection_number VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,
    type ENUM('pppoe', 'hotspot', 'static_ip') NOT NULL,
    status ENUM('pending', 'scheduled', 'installing', 'active', 'suspended', 'disconnected', 'cancelled', 'archived') NOT NULL DEFAULT 'pending',
    
    -- Network Configuration
    pppoe_username VARCHAR(100) NULL,
    pppoe_password VARCHAR(255) NULL, -- Encrypted
    static_ip VARCHAR(45) NULL,
    gateway VARCHAR(45) NULL,
    dns_servers VARCHAR(255) NULL,
    mac_address VARCHAR(17) NULL,
    vlan_id INT UNSIGNED NULL,
    radius_profile VARCHAR(100) NULL,
    queue_name VARCHAR(100) NULL,
    
    -- Infrastructure
    pop_site VARCHAR(100) NULL,
    tower VARCHAR(100) NULL,
    sector VARCHAR(100) NULL,
    access_point VARCHAR(100) NULL,
    assigned_router VARCHAR(100) NULL,
    
    -- Installation Details
    installation_date DATE NULL,
    activation_date DATE NULL,
    installation_team VARCHAR(100) NULL,
    technician_id BIGINT UNSIGNED NULL,
    installation_cost DECIMAL(12,2) DEFAULT 0.00,
    installation_notes TEXT NULL,
    
    -- Billing Summary (Initial integration)
    billing_start_date DATE NULL,
    next_billing_date DATE NULL,
    contract_length_months INT UNSIGNED DEFAULT 1,
    auto_renew TINYINT(1) DEFAULT 1,
    grace_period_days INT UNSIGNED DEFAULT 0,
    
    -- Monitoring Placeholders
    last_online_at TIMESTAMP NULL,
    
    -- Audit & Lifecycle
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_connections_number (connection_number),
    UNIQUE KEY uk_connections_pppoe_username (pppoe_username),
    KEY idx_connections_status (status),
    KEY idx_connections_customer (customer_id),
    KEY idx_connections_package (package_id),
    KEY idx_connections_type (type),
    KEY idx_connections_created_at (created_at),
    KEY idx_connections_deleted_at (deleted_at),
    
    CONSTRAINT fk_connections_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_connections_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_connections_technician FOREIGN KEY (technician_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_connections_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_connections_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Permissions
INSERT INTO permissions (name, description) VALUES
('connections.view', 'View connection lists and details'),
('connections.create', 'Create new internet service connections'),
('connections.update', 'Update connection configurations'),
('connections.delete', 'Soft delete connection records'),
('connections.activate', 'Activate connection and start billing'),
('connections.suspend', 'Suspend internet service'),
('connections.disconnect', 'Permanently disconnect service'),
('connections.transfer', 'Transfer connection to another customer'),
('connections.manage', 'Full management access to connections');
