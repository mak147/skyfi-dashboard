-- MikroTik Integration Platform schema.
-- Router credentials are encrypted by the application before storage.

CREATE TABLE mikrotik_router_groups (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_mikrotik_router_groups_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mikrotik_router_tags (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_mikrotik_router_tags_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mikrotik_routers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    router_group_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    host VARCHAR(253) NOT NULL,
    api_port SMALLINT UNSIGNED NOT NULL DEFAULT 8729,
    api_username VARCHAR(128) NOT NULL,
    api_password_encrypted TEXT NOT NULL,
    routeros_version VARCHAR(100) NULL,
    model VARCHAR(150) NULL,
    location VARCHAR(255) NULL,
    site VARCHAR(150) NULL,
    notes TEXT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_connection_status ENUM('online', 'offline', 'unknown', 'disabled') NOT NULL DEFAULT 'unknown',
    last_connection_error VARCHAR(500) NULL,
    last_connected_at TIMESTAMP NULL,
    last_discovered_at TIMESTAMP NULL,
    last_health_checked_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_mikrotik_routers_name (name),
    KEY idx_mikrotik_routers_group (router_group_id),
    KEY idx_mikrotik_routers_enabled_status (is_enabled, last_connection_status),
    KEY idx_mikrotik_routers_site (site),
    KEY idx_mikrotik_routers_deleted_at (deleted_at),
    CONSTRAINT fk_mikrotik_routers_group FOREIGN KEY (router_group_id) REFERENCES mikrotik_router_groups (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mikrotik_routers_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mikrotik_routers_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mikrotik_router_tag_assignments (
    router_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (router_id, tag_id),
    KEY idx_mikrotik_router_tag_assignments_tag (tag_id),
    CONSTRAINT fk_mikrotik_router_tag_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mikrotik_router_tag_tag FOREIGN KEY (tag_id) REFERENCES mikrotik_router_tags (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mikrotik_router_health_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    router_id BIGINT UNSIGNED NOT NULL,
    status ENUM('online', 'offline', 'unknown', 'disabled') NOT NULL,
    latency_ms DECIMAL(10,3) NULL,
    cpu_usage_percent DECIMAL(5,2) NULL,
    memory_total_bytes BIGINT UNSIGNED NULL,
    memory_free_bytes BIGINT UNSIGNED NULL,
    disk_total_bytes BIGINT UNSIGNED NULL,
    disk_free_bytes BIGINT UNSIGNED NULL,
    temperature_celsius DECIMAL(6,2) NULL,
    traffic_rx_bytes BIGINT UNSIGNED NULL,
    traffic_tx_bytes BIGINT UNSIGNED NULL,
    active_users_count INT UNSIGNED NULL,
    queue_count INT UNSIGNED NULL,
    uptime VARCHAR(100) NULL,
    error_message VARCHAR(500) NULL,
    checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mikrotik_health_router_checked (router_id, checked_at),
    KEY idx_mikrotik_health_status_checked (status, checked_at),
    CONSTRAINT fk_mikrotik_health_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
