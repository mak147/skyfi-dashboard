-- PPPoE Management Module Schema
-- Apply through the project's migration runner; do not alter production manually.

CREATE TABLE pppoe_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password_encrypted TEXT NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    connection_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,
    router_id BIGINT UNSIGNED NOT NULL,
    
    -- Router Configuration & Bindings
    profile VARCHAR(100) NOT NULL,
    service VARCHAR(50) NOT NULL DEFAULT 'pppoe',
    ip_pool VARCHAR(100) NULL,
    static_ip VARCHAR(45) NULL,
    mac_binding VARCHAR(17) NULL,
    caller_id VARCHAR(100) NULL,
    rate_limit VARCHAR(100) NULL,
    session_timeout INT UNSIGNED NULL,
    idle_timeout INT UNSIGNED NULL,
    shared_users INT UNSIGNED NOT NULL DEFAULT 1,
    
    -- Lifecycle & Operational Status
    status ENUM('active', 'disabled', 'suspended', 'pending', 'error') NOT NULL DEFAULT 'pending',
    sync_status ENUM('synced', 'out_of_sync', 'missing_on_router', 'conflict') NOT NULL DEFAULT 'out_of_sync',
    last_connected_at TIMESTAMP NULL,
    last_synced_at TIMESTAMP NULL,
    notes TEXT NULL,
    
    -- Audit & Lifecycle
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_pppoe_accounts_username (username),
    KEY idx_pppoe_customer (customer_id),
    KEY idx_pppoe_connection (connection_id),
    KEY idx_pppoe_package (package_id),
    KEY idx_pppoe_router (router_id),
    KEY idx_pppoe_status_sync (status, sync_status),
    KEY idx_pppoe_deleted_at (deleted_at),
    
    CONSTRAINT fk_pppoe_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_connection FOREIGN KEY (connection_id) REFERENCES connections (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pppoe_session_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    router_id BIGINT UNSIGNED NOT NULL,
    session_id VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(17) NULL,
    caller_id VARCHAR(100) NULL,
    uptime_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
    bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
    bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
    started_at DATETIME NOT NULL,
    ended_at DATETIME NOT NULL,
    disconnect_reason VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    KEY idx_pppoe_history_account (account_id, started_at),
    KEY idx_pppoe_history_router (router_id, started_at),
    KEY idx_pppoe_history_username (username),
    
    CONSTRAINT fk_pppoe_history_account FOREIGN KEY (account_id) REFERENCES pppoe_accounts (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_history_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pppoe_sync_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    router_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NULL,
    action ENUM('sync_user', 'detect_missing', 'repair_user', 'import_users', 'conflict_resolved') NOT NULL,
    status ENUM('success', 'failed', 'conflict', 'warning') NOT NULL,
    message VARCHAR(1000) NOT NULL,
    diff_payload JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    KEY idx_pppoe_sync_router_status (router_id, status, created_at),
    KEY idx_pppoe_sync_account (account_id, created_at),
    
    CONSTRAINT fk_pppoe_sync_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_sync_account FOREIGN KEY (account_id) REFERENCES pppoe_accounts (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pppoe_auth_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    router_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NULL,
    username VARCHAR(100) NOT NULL,
    caller_id VARCHAR(100) NULL,
    mac_address VARCHAR(17) NULL,
    status ENUM('success', 'failed') NOT NULL,
    reason VARCHAR(500) NULL,
    attempted_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    KEY idx_pppoe_auth_router_time (router_id, attempted_at),
    KEY idx_pppoe_auth_username_status (username, status),
    
    CONSTRAINT fk_pppoe_auth_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pppoe_auth_account FOREIGN KEY (account_id) REFERENCES pppoe_accounts (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
