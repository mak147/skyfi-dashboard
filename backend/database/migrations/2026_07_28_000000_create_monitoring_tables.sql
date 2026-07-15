-- Network Monitoring & Observability Module schema.
-- Reuses existing MikroTik Integration and Infrastructure modules.

CREATE TABLE IF NOT EXISTS monitoring_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('device_status_change', 'interface_status_change', 'health_check', 'sync_event', 'alert_triggered', 'threshold_violation') NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL DEFAULT 'info',
    source_type ENUM('mikrotik_router', 'network_device', 'pppoe_session', 'hotspot_session', 'system') NOT NULL,
    source_id BIGINT UNSIGNED NULL,
    message VARCHAR(500) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mon_events_type_sev (event_type, severity),
    INDEX idx_mon_events_source (source_type, source_id),
    INDEX idx_mon_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_device_status_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    device_type ENUM('mikrotik_router', 'network_device') NOT NULL,
    device_id BIGINT UNSIGNED NOT NULL,
    status ENUM('online', 'offline', 'degraded', 'maintenance', 'unknown') NOT NULL,
    latency_ms DECIMAL(10,3) NULL,
    error_message VARCHAR(500) NULL,
    checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mon_status_device (device_type, device_id, checked_at),
    INDEX idx_mon_status_checked (checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_interface_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    router_id BIGINT UNSIGNED NOT NULL,
    interface_name VARCHAR(100) NOT NULL,
    interface_type VARCHAR(50) NULL,
    running TINYINT(1) NOT NULL DEFAULT 0,
    disabled TINYINT(1) NOT NULL DEFAULT 0,
    mtu INT UNSIGNED NULL,
    rx_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    tx_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    rx_bps BIGINT UNSIGNED NOT NULL DEFAULT 0,
    tx_bps BIGINT UNSIGNED NOT NULL DEFAULT 0,
    link_status ENUM('up', 'down', 'degraded') NOT NULL DEFAULT 'down',
    checked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mon_iface_router_checked (router_id, checked_at),
    INDEX idx_mon_iface_name (interface_name),
    CONSTRAINT fk_mon_iface_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('device_offline', 'high_cpu', 'high_memory', 'interface_down', 'link_degradation', 'auth_failure', 'sync_failure') NOT NULL,
    severity ENUM('info', 'warning', 'critical') NOT NULL,
    status ENUM('new', 'acknowledged', 'resolved', 'dismissed') NOT NULL DEFAULT 'new',
    device_type ENUM('mikrotik_router', 'network_device', 'system') NOT NULL,
    device_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    metric_value VARCHAR(100) NULL,
    threshold_value VARCHAR(100) NULL,
    metadata JSON NULL,
    triggered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    resolved_by BIGINT UNSIGNED NULL,
    dismissed_at TIMESTAMP NULL,
    dismissed_by BIGINT UNSIGNED NULL,
    resolution_notes VARCHAR(500) NULL,
    INDEX idx_mon_alerts_status_sev (status, severity),
    INDEX idx_mon_alerts_device (device_type, device_id),
    INDEX idx_mon_alerts_triggered (triggered_at),
    CONSTRAINT fk_mon_alerts_ack_by FOREIGN KEY (acknowledged_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_mon_alerts_res_by FOREIGN KEY (resolved_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_mon_alerts_dis_by FOREIGN KEY (dismissed_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_alert_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    alert_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    changed_by BIGINT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mon_alert_hist_alert (alert_id, created_at),
    CONSTRAINT fk_mon_alert_hist_alert FOREIGN KEY (alert_id) REFERENCES monitoring_alerts (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mon_alert_hist_user FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monitoring_sync_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    router_id BIGINT UNSIGNED NULL,
    sync_type ENUM('health_check', 'interface_poll', 'pppoe_sync', 'hotspot_sync', 'full_topology_sync') NOT NULL,
    status ENUM('success', 'failed', 'partial') NOT NULL,
    items_synced INT UNSIGNED NOT NULL DEFAULT 0,
    error_message VARCHAR(500) NULL,
    execution_time_ms DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mon_sync_router (router_id, created_at),
    INDEX idx_mon_sync_type_status (sync_type, status),
    CONSTRAINT fk_mon_sync_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed RBAC Permissions for Monitoring Module
-- ============================================================
INSERT INTO permissions (name, description, module) VALUES
  ('monitoring.view', 'View network monitoring dashboards, device status, metrics, and alerts', 'monitoring'),
  ('monitoring.check', 'Trigger manual health checks, device polling, and interface telemetry check', 'monitoring'),
  ('monitoring.alerts', 'Acknowledge, resolve, dismiss, and manage network alerts', 'monitoring'),
  ('monitoring.manage', 'Manage monitoring thresholds, log cleanup, and advanced operations', 'monitoring')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Assign all monitoring permissions to Super Administrator role
INSERT INTO permission_role (permission_id, role_id)
SELECT p.id, r.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Super Administrator'
  AND p.name LIKE 'monitoring.%'
ON DUPLICATE KEY UPDATE role_id = role_id;
