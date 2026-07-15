-- SkyFi Hotspot Management Module
-- Database Migration: Create all hotspot tables
-- Date: 2026-07-14

-- ============================================================
-- Table: hotspot_profiles
-- ============================================================
CREATE TABLE IF NOT EXISTS hotspot_profiles (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  router_id     BIGINT UNSIGNED NOT NULL,
  router_profile_name VARCHAR(100) NOT NULL COMMENT 'Maps to MikroTik profile name',
  rate_limit_up VARCHAR(50) DEFAULT NULL COMMENT 'Upload rate limit (e.g. 5M)',
  rate_limit_down VARCHAR(50) DEFAULT NULL COMMENT 'Download rate limit (e.g. 10M)',
  session_timeout INT UNSIGNED DEFAULT NULL COMMENT 'Seconds',
  idle_timeout  INT UNSIGNED DEFAULT NULL COMMENT 'Seconds',
  shared_users  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  mac_cookie_timeout VARCHAR(50) DEFAULT NULL COMMENT 'e.g. 3d for 3 days',
  login_methods VARCHAR(255) DEFAULT 'http-pap' COMMENT 'Comma-separated',
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  sync_status   ENUM('synced','out_of_sync','missing_on_router','conflict') NOT NULL DEFAULT 'out_of_sync',
  notes         TEXT DEFAULT NULL,
  last_synced_at TIMESTAMP NULL,
  created_by    INT UNSIGNED DEFAULT NULL,
  updated_by    INT UNSIGNED DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    TIMESTAMP NULL,
  UNIQUE KEY uk_hotspot_profiles_router_name (router_id, router_profile_name, deleted_at),
  INDEX idx_hotspot_profiles_status (status),
  INDEX idx_hotspot_profiles_router (router_id),
  CONSTRAINT fk_hotspot_profiles_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: hotspot_users
-- ============================================================
CREATE TABLE IF NOT EXISTS hotspot_users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(100) NOT NULL,
  password_encrypted TEXT NOT NULL,
  customer_id   BIGINT UNSIGNED DEFAULT NULL COMMENT 'Nullable for voucher-only users',
  connection_id BIGINT UNSIGNED DEFAULT NULL,
  package_id    BIGINT UNSIGNED DEFAULT NULL,
  router_id     BIGINT UNSIGNED NOT NULL,
  profile_id    BIGINT UNSIGNED DEFAULT NULL,
  profile_name  VARCHAR(100) NOT NULL DEFAULT 'default',
  limit_uptime  VARCHAR(50) DEFAULT NULL COMMENT 'e.g. 1d, 8h',
  limit_bytes_in BIGINT UNSIGNED DEFAULT NULL,
  limit_bytes_out BIGINT UNSIGNED DEFAULT NULL,
  limit_bytes_total BIGINT UNSIGNED DEFAULT NULL,
  mac_address   VARCHAR(17) DEFAULT NULL COMMENT 'XX:XX:XX:XX:XX:XX',
  status        ENUM('active','disabled','suspended','pending','error') NOT NULL DEFAULT 'active',
  sync_status   ENUM('synced','out_of_sync','missing_on_router','conflict') NOT NULL DEFAULT 'out_of_sync',
  last_connected_at TIMESTAMP NULL,
  last_synced_at TIMESTAMP NULL,
  notes         TEXT DEFAULT NULL,
  created_by    INT UNSIGNED DEFAULT NULL,
  updated_by    INT UNSIGNED DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    TIMESTAMP NULL,
  UNIQUE KEY uk_hotspot_users_username (username, deleted_at),
  INDEX idx_hotspot_users_router (router_id),
  INDEX idx_hotspot_users_customer (customer_id),
  INDEX idx_hotspot_users_profile (profile_id),
  INDEX idx_hotspot_users_status (status),
  INDEX idx_hotspot_users_sync (sync_status),
  CONSTRAINT fk_hotspot_users_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_hotspot_users_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_hotspot_users_profile FOREIGN KEY (profile_id) REFERENCES hotspot_profiles(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: hotspot_voucher_batches
-- ============================================================
CREATE TABLE IF NOT EXISTS hotspot_voucher_batches (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_code    VARCHAR(50) NOT NULL COMMENT 'Unique batch identifier',
  hotspot_profile_id BIGINT UNSIGNED NOT NULL,
  router_id     BIGINT UNSIGNED NOT NULL,
  quantity      INT UNSIGNED NOT NULL,
  prefix        VARCHAR(20) DEFAULT NULL COMMENT 'Custom prefix for voucher codes',
  price_per_voucher DECIMAL(10,2) DEFAULT NULL,
  time_limit    VARCHAR(50) DEFAULT NULL COMMENT 'e.g. 1d, 8h',
  data_limit_mb BIGINT UNSIGNED DEFAULT NULL COMMENT 'MB',
  validity_days INT UNSIGNED DEFAULT NULL COMMENT 'Days until voucher expires from generation',
  status        ENUM('active','exhausted','expired','cancelled') NOT NULL DEFAULT 'active',
  generated_by  INT UNSIGNED DEFAULT NULL,
  notes         TEXT DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    TIMESTAMP NULL,
  UNIQUE KEY uk_voucher_batches_code (batch_code),
  INDEX idx_voucher_batches_profile (hotspot_profile_id),
  INDEX idx_voucher_batches_router (router_id),
  INDEX idx_voucher_batches_status (status),
  CONSTRAINT fk_voucher_batches_profile FOREIGN KEY (hotspot_profile_id) REFERENCES hotspot_profiles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_voucher_batches_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: hotspot_vouchers
-- ============================================================
CREATE TABLE IF NOT EXISTS hotspot_vouchers (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(50) NOT NULL COMMENT 'The unique voucher code',
  batch_id      BIGINT UNSIGNED NOT NULL,
  hotspot_user_id BIGINT UNSIGNED DEFAULT NULL COMMENT 'Linked user after redemption',
  status        ENUM('new','used','expired','revoked') NOT NULL DEFAULT 'new',
  time_limit    VARCHAR(50) DEFAULT NULL,
  data_limit_mb BIGINT UNSIGNED DEFAULT NULL,
  price         DECIMAL(10,2) DEFAULT NULL,
  expires_at    TIMESTAMP NULL,
  used_at       TIMESTAMP NULL,
  used_by_mac   VARCHAR(17) DEFAULT NULL,
  used_by_ip    VARCHAR(45) DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at    TIMESTAMP NULL,
  UNIQUE KEY uk_hotspot_vouchers_code (code),
  INDEX idx_hotspot_vouchers_batch (batch_id),
  INDEX idx_hotspot_vouchers_status (status),
  INDEX idx_hotspot_vouchers_expires (expires_at),
  INDEX idx_hotspot_vouchers_hotspot_user (hotspot_user_id),
  CONSTRAINT fk_hotspot_vouchers_batch FOREIGN KEY (batch_id) REFERENCES hotspot_voucher_batches(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_hotspot_vouchers_user FOREIGN KEY (hotspot_user_id) REFERENCES hotspot_users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: hotspot_session_history
-- ============================================================
CREATE TABLE IF NOT EXISTS hotspot_session_history (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hotspot_user_id BIGINT UNSIGNED DEFAULT NULL,
  router_id     BIGINT UNSIGNED NOT NULL,
  session_id    VARCHAR(100) DEFAULT NULL COMMENT 'RouterOS .id or unique identifier',
  username      VARCHAR(100) NOT NULL,
  mac_address   VARCHAR(17) DEFAULT NULL,
  ip_address    VARCHAR(45) DEFAULT NULL,
  uptime_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  bytes_in      BIGINT UNSIGNED NOT NULL DEFAULT 0,
  bytes_out     BIGINT UNSIGNED NOT NULL DEFAULT 0,
  started_at    TIMESTAMP NOT NULL,
  ended_at      TIMESTAMP NULL,
  disconnect_reason VARCHAR(100) DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hs_session_user (hotspot_user_id),
  INDEX idx_hs_session_router (router_id),
  INDEX idx_hs_session_username (username),
  INDEX idx_hs_session_started (started_at),
  CONSTRAINT fk_hs_session_user FOREIGN KEY (hotspot_user_id) REFERENCES hotspot_users(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_hs_session_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: hotspot_sync_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS hotspot_sync_logs (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  router_id     BIGINT UNSIGNED NOT NULL,
  hotspot_user_id BIGINT UNSIGNED DEFAULT NULL,
  action        VARCHAR(50) NOT NULL COMMENT 'sync_user, sync_router, sync_profile, import_users, repair, voucher_redeem',
  status        ENUM('success','failed','warning','conflict') NOT NULL DEFAULT 'success',
  message       TEXT NOT NULL,
  diff_payload  JSON DEFAULT NULL,
  created_by    INT UNSIGNED DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hs_sync_router (router_id),
  INDEX idx_hs_sync_user (hotspot_user_id),
  INDEX idx_hs_sync_created (created_at),
  CONSTRAINT fk_hs_sync_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: hotspot_login_history
-- ============================================================
CREATE TABLE IF NOT EXISTS hotspot_login_history (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  router_id     BIGINT UNSIGNED NOT NULL,
  hotspot_user_id BIGINT UNSIGNED DEFAULT NULL,
  username      VARCHAR(100) NOT NULL,
  mac_address   VARCHAR(17) DEFAULT NULL,
  ip_address    VARCHAR(45) DEFAULT NULL,
  status        ENUM('success','failed') NOT NULL,
  reason        VARCHAR(255) DEFAULT NULL,
  attempted_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hs_login_router (router_id),
  INDEX idx_hs_login_user (hotspot_user_id),
  INDEX idx_hs_login_status (status, attempted_at),
  CONSTRAINT fk_hs_login_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed RBAC Permissions
-- ============================================================
INSERT INTO permissions (name, description, module) VALUES
  ('hotspot.view', 'View hotspot users, profiles, vouchers, sessions', 'hotspot'),
  ('hotspot.create', 'Create hotspot users and profiles', 'hotspot'),
  ('hotspot.update', 'Update hotspot users and profiles, reset passwords', 'hotspot'),
  ('hotspot.delete', 'Delete hotspot users and profiles', 'hotspot'),
  ('hotspot.sync', 'Run synchronization, import, repair operations', 'hotspot'),
  ('hotspot.monitor', 'View active sessions, disconnect users, view history', 'hotspot'),
  ('hotspot.vouchers', 'Generate, view, revoke, and print vouchers', 'hotspot'),
  ('hotspot.manage', 'Suspend/resume users, force logout, advanced operations', 'hotspot')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Assign all hotspot permissions to Super Administrator role
INSERT INTO permission_role (permission_id, role_id)
SELECT p.id, r.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Super Administrator'
  AND p.name LIKE 'hotspot.%'
ON DUPLICATE KEY UPDATE role_id = role_id;
