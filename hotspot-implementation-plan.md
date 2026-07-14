# Hotspot Management Module — Implementation Plan

**Project:** SkyFi Networks ISP Management System  
**Module:** Hotspot Management  
**Date:** 2026-07-14  
**Status:** Pending Approval  

---

## 1. Architectural Overview

### 1.1 Design Philosophy

The Hotspot module follows the **exact same layered architecture** established by the PPPoE module (Document 08), reusing the MikroTik Integration Platform without creating duplicate infrastructure. It mirrors the PPPoE module's patterns while extending them for hotspot-specific concepts: vouchers, profiles, batch generation, and captive portal user management.

### 1.2 Reused Infrastructure (Zero Duplication)

| Existing Component | Reuse Purpose |
|---|---|
| `MikrotikConnectionPoolContract` | Router API connections via `/ip/hotspot/*` paths |
| `RouterServiceContract` | Router lookup, connection data, profiles |
| `CredentialCipherContract` | Password encryption for hotspot users |
| `RequirePermissionMiddleware` | RBAC permission enforcement |
| `AuditLoggerContract` | Full audit trail for all mutations |
| `Shared/Http/ApiResponse` | Standardized JSON:API responses |
| `Shared/Http/Request` | Request parsing (query, body, attributes) |
| `Shared/Exceptions/*` | NotFoundException, ValidationException, etc. |
| `Shared/Events/EventDispatcher` | Domain events (optional async integration) |
| Customer, Package, Connection repos | Cross-module entity lookups |
| `apiClient` (frontend) | Axios-based API with JWT refresh |
| `usePermissions` hook | Frontend RBAC gating |
| SkyFi Tailwind design system | Consistent UI patterns |

### 1.3 MikroTik RouterOS API Mapping

| SkyFi Function | RouterOS Path | Key Parameters |
|---|---|---|
| Create Hotspot User | `/ip/hotspot/user/add` | name, password, profile, limit-uptime, limit-bytes-in/out |
| Update Hotspot User | `/ip/hotspot/user/set` | .id, profile, disabled, limit-* |
| Remove Hotspot User | `/ip/hotspot/user/remove` | .id |
| Get Hotspot Users | `/ip/hotspot/user/print` | (query by name, profile) |
| Get Active Sessions | `/ip/hotspot/active/print` | (returns uptime, bytes-in, bytes-out, mac-address) |
| Disconnect Active | `/ip/hotspot/active/remove` | .id |
| Get Hotspot Profiles | `/ip/hotspot/user/profile/print` | name, rate-limit, session-timeout, etc. |
| Get Hotspot Servers | `/ip/hotspot/print` | name, interface, address-pool |

---

## 2. Database Schema

### 2.1 Table: `hotspot_profiles`

```sql
CREATE TABLE hotspot_profiles (
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
```

### 2.2 Table: `hotspot_users`

```sql
CREATE TABLE hotspot_users (
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
  CONSTRAINT fk_hotspot_users_connection FOREIGN KEY (connection_id) REFERENCES connections(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_hotspot_users_package FOREIGN KEY (package_id) REFERENCES internet_packages(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_hotspot_users_profile FOREIGN KEY (profile_id) REFERENCES hotspot_profiles(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.3 Table: `hotspot_voucher_batches`

```sql
CREATE TABLE hotspot_voucher_batches (
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
```

### 2.4 Table: `hotspot_vouchers`

```sql
CREATE TABLE hotspot_vouchers (
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
```

### 2.5 Table: `hotspot_session_history`

```sql
CREATE TABLE hotspot_session_history (
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
```

### 2.6 Table: `hotspot_sync_logs`

```sql
CREATE TABLE hotspot_sync_logs (
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
```

### 2.7 Table: `hotspot_login_history`

```sql
CREATE TABLE hotspot_login_history (
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
```

---

## 3. Backend Implementation (`backend/src/Hotspot/`)

### 3.1 File Structure

```
backend/src/Hotspot/
├── Contracts/
│   ├── HotspotUserServiceContract.php
│   ├── HotspotUserRepositoryContract.php
│   ├── HotspotProfileRepositoryContract.php
│   ├── HotspotProfileServiceContract.php
│   ├── VoucherServiceContract.php
│   ├── VoucherRepositoryContract.php
│   ├── VoucherBatchRepositoryContract.php
│   ├── HotspotSessionRepositoryContract.php
│   └── HotspotSyncLoggerContract.php
├── Controllers/
│   ├── HotspotUserController.php
│   ├── HotspotProfileController.php
│   ├── VoucherController.php
│   ├── HotspotSessionController.php
│   └── HotspotSyncController.php
├── DTOs/
│   ├── CreateHotspotUserData.php
│   ├── UpdateHotspotUserData.php
│   ├── HotspotUserListFilters.php
│   ├── CreateHotspotProfileData.php
│   ├── UpdateHotspotProfileData.php
│   ├── HotspotProfileListFilters.php
│   ├── GenerateVoucherBatchData.php
│   ├── VoucherListFilters.php
│   ├── ImportHotspotUsersData.php
│   ├── SyncOptionsData.php
│   └── BulkImportUserData.php
├── DomainModels/
│   ├── HotspotUser.php
│   ├── HotspotProfile.php
│   ├── Voucher.php
│   ├── VoucherBatch.php
│   ├── HotspotActiveSession.php
│   ├── HotspotSessionHistory.php
│   ├── HotspotSyncResult.php
│   └── HotspotLoginRecord.php
├── Repositories/
│   ├── PdoHotspotUserRepository.php
│   ├── PdoHotspotProfileRepository.php
│   ├── PdoVoucherRepository.php
│   ├── PdoVoucherBatchRepository.php
│   ├── PdoHotspotSessionRepository.php
│   └── PdoHotspotSyncLogger.php
├── Services/
│   ├── HotspotUserService.php
│   ├── HotspotProfileService.php
│   ├── VoucherService.php
│   ├── HotspotSessionMonitorService.php
│   └── HotspotSyncService.php
├── Validators/
│   ├── HotspotUserValidator.php
│   ├── HotspotProfileValidator.php
│   └── VoucherValidator.php
└── Routes/
    └── hotspot.php
```

### 3.2 Services (Core Business Logic)

#### 3.2.1 `HotspotUserService` (implements `HotspotUserServiceContract`)

**Dependencies:** HotspotUserRepositoryContract, HotspotProfileRepositoryContract, CustomerRepositoryContract, ConnectionRepositoryContract, PackageRepositoryContract, RouterServiceContract, MikrotikConnectionPoolContract, CredentialCipherContract, HotspotSyncLoggerContract, HotspotUserValidator, AuditLoggerContract

**Methods:**
- `list(HotspotUserListFilters): array` — Paginated list with enrichment (router name, customer name, profile name)
- `get(int): HotspotUser` — Single user with enrichment
- `create(CreateHotspotUserData, actorId, ip, ua): HotspotUser` — Validates, inserts, pushes to MikroTik via `/ip/hotspot/user/add`, logs sync
- `update(int, UpdateHotspotUserData, actorId, ip, ua): HotspotUser` — Partial update, pushes `/ip/hotspot/user/set`
- `delete(int, actorId, ip, ua): void` — Soft-delete, removes from router via `/ip/hotspot/user/remove`
- `setEnabled(int, bool, actorId, ip, ua): HotspotUser` — Toggle active/disabled, pushes disabled flag to router
- `suspend(int, actorId, ip, ua): HotspotUser` — Sets status to suspended, disconnects active sessions
- `resume(int, actorId, ip, ua): HotspotUser` — Sets status to active
- `resetPassword(int, string, actorId, ip, ua): HotspotUser` — Reset and push to router
- `assignProfile(int, int, actorId, ip, ua): HotspotUser` — Change hotspot profile
- `assignRouter(int, int, actorId, ip, ua): HotspotUser` — Move user to different router
- `bulkImport(BulkImportUserData, actorId, ip, ua): array` — CSV/array batch import with router push

**MikroTik Integration Pattern (matching PPPoE):**
```php
// Push to router
$connection = $this->routerService->connectionData($user->routerId());
$responses = $this->pool->executeBatch($connection, [
    ['/ip/hotspot/user/print', '?name=' . $user->username()]
]);
$existingId = $responses[0][0]['.id'] ?? null;

if ($existingId) {
    $this->pool->executeBatch($connection, [
        ['/ip/hotspot/user/set', '=.id=' . $existingId, '=profile=' . $user->profileName(), '=disabled=' . ($user->status() !== 'active' ? 'yes' : 'no')]
    ]);
} else {
    $sentence = ['/ip/hotspot/user/add', '=name=' . $user->username(), '=password=' . $passwordPlain, '=profile=' . $user->profileName()];
    // Add optional limits...
    $this->pool->executeBatch($connection, [$sentence]);
}
```

#### 3.2.2 `HotspotProfileService` (implements `HotspotProfileServiceContract`)

**Dependencies:** HotspotProfileRepositoryContract, RouterServiceContract, MikrotikConnectionPoolContract, HotspotProfileValidator, AuditLoggerContract

**Methods:**
- `list(HotspotProfileListFilters): array`
- `get(int): HotspotProfile`
- `create(CreateHotspotProfileData, actorId, ip, ua): HotspotProfile`
- `update(int, UpdateHotspotProfileData, actorId, ip, ua): HotspotProfile`
- `delete(int, actorId, ip, ua): void`
- `fetchRouterProfiles(int routerId): array` — Fetches `/ip/hotspot/user/profile/print` from router
- `syncWithRouter(int profileId): HotspotProfile` — Verify/sync profile state

#### 3.2.3 `VoucherService` (implements `VoucherServiceContract`)

**Dependencies:** VoucherRepositoryContract, VoucherBatchRepositoryContract, HotspotUserRepositoryContract, HotspotProfileRepositoryContract, RouterServiceContract, MikrotikConnectionPoolContract, CredentialCipherContract, HotspotSyncLoggerContract, VoucherValidator, AuditLoggerContract

**Methods:**
- `listVouchers(VoucherListFilters): array` — Paginated voucher list
- `listBatches(int page, int perPage, ?string status): array` — Batch list
- `getVoucher(int): Voucher`
- `generateBatch(GenerateVoucherBatchData, actorId, ip, ua): VoucherBatch` — Creates batch + N voucher codes with cryptographically secure random generation
- `redeemVoucher(string code, ?string macAddress, ?string ipAddress): array` — Validates voucher, creates temporary hotspot user on router, returns credentials
- `revokeVoucher(int, actorId, ip, ua): Voucher`
- `expireVouchers(): int` — Cleanup expired vouchers (for scheduled job)
- `printVouchers(int batchId): array` — Returns print-ready voucher data
- `getVoucherStats(): array` — Stats for dashboard

**Voucher Code Generation:**
```php
// Cryptographically secure, collision-free codes
private function generateCode(string $prefix = ''): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No I/O/0/1 confusion
    $code = $prefix;
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
```

#### 3.2.4 `HotspotSessionMonitorService`

**Dependencies:** HotspotUserRepositoryContract, HotspotSessionRepositoryContract, RouterServiceContract, MikrotikConnectionPoolContract, AuditLoggerContract

**Methods:**
- `listActiveSessions(?int routerId): array` — Fetches `/ip/hotspot/active/print` across routers, enriches with DB data
- `disconnectSession(int routerId, string sessionId, actorId, ip, ua): void` — `/ip/hotspot/active/remove`
- `forceLogout(string username, actorId, ip, ua): void` — Disconnects all active sessions for a user
- `listSessionHistory(int page, int perPage, ?int userId, ?int routerId, ?string username): array`
- `getLoginHistory(int page, int perPage, ?int userId, ?int routerId): array`
- `getUserStatistics(int userId): array` — Aggregate uptime, bytes, session count

#### 3.2.5 `HotspotSyncService`

**Dependencies:** HotspotUserRepositoryContract, HotspotProfileRepositoryContract, RouterServiceContract, MikrotikConnectionPoolContract, CredentialCipherContract, HotspotSyncLoggerContract

**Methods:**
- `syncRouter(int routerId, ?int actorId): HotspotSyncResult` — Full audit: compares DB users vs router users, detects missing/orphan/conflicts
- `syncAccount(int userId, ?int actorId): HotspotSyncResult`
- `detectMissing(?int routerId): array` — Find accounts missing from routers
- `repair(SyncOptionsData, actorId): array` — Push missing/conflict users to router
- `importUsers(ImportHotspotUsersData, actorId): array` — Import users from router `/ip/hotspot/user/print`
- `importProfiles(int routerId, actorId): array` — Import profiles from router
- `listRouterProfiles(int routerId): array` — List profiles on a specific router
- `listSyncLogs(int limit, ?int routerId, ?int userId): array`

### 3.3 API Routes (`Routes/hotspot.php`)

```
# Hotspot User Management
GET    /api/v1/hotspot/users                         → HotspotUserController@index     [hotspot.view]
POST   /api/v1/hotspot/users                         → HotspotUserController@store      [hotspot.create]
GET    /api/v1/hotspot/users/{id}                    → HotspotUserController@show       [hotspot.view]
PUT    /api/v1/hotspot/users/{id}                    → HotspotUserController@update     [hotspot.update]
DELETE /api/v1/hotspot/users/{id}                    → HotspotUserController@destroy    [hotspot.delete]
PATCH  /api/v1/hotspot/users/{id}/enable             → HotspotUserController@enable     [hotspot.update]
PATCH  /api/v1/hotspot/users/{id}/disable            → HotspotUserController@disable    [hotspot.update]
POST   /api/v1/hotspot/users/{id}/suspend            → HotspotUserController@suspend    [hotspot.manage]
POST   /api/v1/hotspot/users/{id}/resume             → HotspotUserController@resume     [hotspot.manage]
POST   /api/v1/hotspot/users/{id}/reset-password     → HotspotUserController@resetPassword [hotspot.update]
PUT    /api/v1/hotspot/users/{id}/profile            → HotspotUserController@assignProfile  [hotspot.update]
PUT    /api/v1/hotspot/users/{id}/router             → HotspotUserController@assignRouter   [hotspot.update]
POST   /api/v1/hotspot/users/bulk-import             → HotspotUserController@bulkImport [hotspot.create]

# Hotspot Profile Management
GET    /api/v1/hotspot/profiles                      → HotspotProfileController@index   [hotspot.view]
POST   /api/v1/hotspot/profiles                      → HotspotProfileController@store   [hotspot.create]
GET    /api/v1/hotspot/profiles/{id}                 → HotspotProfileController@show    [hotspot.view]
PUT    /api/v1/hotspot/profiles/{id}                 → HotspotProfileController@update  [hotspot.update]
DELETE /api/v1/hotspot/profiles/{id}                 → HotspotProfileController@destroy [hotspot.delete]

# Voucher Management
GET    /api/v1/hotspot/vouchers                      → VoucherController@index          [hotspot.vouchers]
POST   /api/v1/hotspot/vouchers/generate             → VoucherController@generate       [hotspot.vouchers]
GET    /api/v1/hotspot/vouchers/batches              → VoucherController@batches        [hotspot.vouchers]
GET    /api/v1/hotspot/vouchers/{id}                 → VoucherController@show           [hotspot.vouchers]
POST   /api/v1/hotspot/vouchers/{id}/revoke          → VoucherController@revoke         [hotspot.vouchers]
GET    /api/v1/hotspot/vouchers/batch/{batchId}/print → VoucherController@printBatch    [hotspot.vouchers]
GET    /api/v1/hotspot/vouchers/stats                → VoucherController@stats          [hotspot.vouchers]

# Active Session Management
GET    /api/v1/hotspot/sessions/active               → HotspotSessionController@activeSessions   [hotspot.monitor]
POST   /api/v1/hotspot/sessions/active/disconnect    → HotspotSessionController@disconnectSession [hotspot.monitor]
POST   /api/v1/hotspot/sessions/force-logout         → HotspotSessionController@forceLogout      [hotspot.monitor]
GET    /api/v1/hotspot/sessions/history              → HotspotSessionController@sessionHistory    [hotspot.monitor]
GET    /api/v1/hotspot/users/{id}/sessions/history   → HotspotSessionController@userSessionHistory [hotspot.monitor]
GET    /api/v1/hotspot/users/{id}/statistics         → HotspotSessionController@statistics        [hotspot.monitor]
GET    /api/v1/hotspot/sessions/login-history        → HotspotSessionController@loginHistory      [hotspot.monitor]

# Synchronization
POST   /api/v1/hotspot/sync/router/{routerId}        → HotspotSyncController@syncRouter    [hotspot.sync]
POST   /api/v1/hotspot/sync/user/{id}                → HotspotSyncController@syncUser      [hotspot.sync]
POST   /api/v1/hotspot/sync/detect-missing           → HotspotSyncController@detectMissing  [hotspot.sync]
POST   /api/v1/hotspot/sync/repair                   → HotspotSyncController@repair         [hotspot.sync]
POST   /api/v1/hotspot/sync/import                   → HotspotSyncController@importUsers    [hotspot.sync]
POST   /api/v1/hotspot/sync/import-profiles          → HotspotSyncController@importProfiles [hotspot.sync]
GET    /api/v1/hotspot/routers/{routerId}/profiles   → HotspotSyncController@routerProfiles [hotspot.view]
GET    /api/v1/hotspot/sync/logs                     → HotspotSyncController@syncLogs       [hotspot.sync]
```

### 3.4 RBAC Permissions

| Permission | Description |
|---|---|
| `hotspot.view` | View hotspot users, profiles, vouchers, sessions |
| `hotspot.create` | Create hotspot users and profiles |
| `hotspot.update` | Update hotspot users and profiles, reset passwords |
| `hotspot.delete` | Delete hotspot users and profiles |
| `hotspot.sync` | Run synchronization, import, repair operations |
| `hotspot.monitor` | View active sessions, disconnect users, view history |
| `hotspot.vouchers` | Generate, view, revoke, and print vouchers |
| `hotspot.manage` | Suspend/resume users, force logout, advanced operations |

### 3.5 DI Container Registration (Container.php additions)

Following the exact pattern from the PPPoE module:
- Register all Pdo* repositories with contract bindings
- Register all validators
- Register all services with their dependencies
- Register all controllers with service + authorizer

---

## 4. Frontend Implementation (`frontend/src/features/hotspot/`)

### 4.1 File Structure

```
frontend/src/features/hotspot/
├── api/
│   └── useHotspot.ts                    # API functions + React Query hooks
├── components/
│   ├── HotspotUserTable.tsx             # Main user listing table
│   ├── HotspotUserForm.tsx              # Create/Edit user form
│   ├── HotspotProfileTable.tsx          # Profile listing table
│   ├── HotspotProfileForm.tsx           # Create/Edit profile form
│   ├── VoucherTable.tsx                 # Voucher listing table
│   ├── VoucherGenerator.tsx             # Batch generation form
│   ├── VoucherPrintView.tsx             # Print-ready voucher layout
│   ├── VoucherBatchCard.tsx             # Batch summary card
│   ├── SessionTable.tsx                 # Active sessions table
│   ├── SessionTimeline.tsx              # Session history timeline
│   ├── SyncStatusCard.tsx               # Sync audit results card
│   ├── SyncLogTable.tsx                 # Sync log entries table
│   ├── RouterSelector.tsx               # Router dropdown (reusable pattern)
│   ├── ProfileSelector.tsx              # Hotspot profile dropdown
│   ├── UsageStatistics.tsx              # Bytes/uptime stats display
│   ├── HotspotUserStatusBadge.tsx       # Status badge component
│   └── HotspotFilters.tsx               # Filter panel component
├── pages/
│   ├── HotspotUsersPage.tsx             # User listing with tabs/filters
│   ├── CreateUserPage.tsx               # Create new user
│   ├── EditUserPage.tsx                 # Edit existing user
│   ├── UserDetailsPage.tsx              # User detail with stats/sessions
│   ├── HotspotProfilesPage.tsx          # Profile CRUD listing
│   ├── CreateProfilePage.tsx            # Create new profile
│   ├── EditProfilePage.tsx              # Edit existing profile
│   ├── VouchersPage.tsx                 # Voucher listing + batch view
│   ├── GenerateVouchersPage.tsx         # Generate voucher batch
│   ├── ActiveSessionsPage.tsx           # Live session monitoring
│   ├── SynchronizationPage.tsx          # Sync audit & repair
│   ├── ImportUsersPage.tsx              # Import from router
│   └── ImportProfilesPage.tsx           # Import profiles from router
├── routes.tsx                           # Permission-gated route definitions
├── schemas.ts                           # Zod validation schemas
└── types.ts                             # TypeScript interfaces
```

### 4.2 TypeScript Types (`types.ts`)

```typescript
export type HotspotUserStatus = 'active' | 'disabled' | 'suspended' | 'pending' | 'error';
export type HotspotSyncStatus = 'synced' | 'out_of_sync' | 'missing_on_router' | 'conflict';
export type VoucherStatus = 'new' | 'used' | 'expired' | 'revoked';
export type VoucherBatchStatus = 'active' | 'exhausted' | 'expired' | 'cancelled';

export interface HotspotUser { id, username, customer_id?, router_id, profile_id?, profile_name, limit_uptime?, limit_bytes_total?, mac_address?, status, sync_status, ... }
export interface HotspotProfile { id, name, router_id, router_profile_name, rate_limit_up?, rate_limit_down?, session_timeout?, idle_timeout?, shared_users, ... }
export interface Voucher { id, code, batch_id, hotspot_user_id?, status, time_limit?, data_limit_mb?, price?, expires_at?, used_at?, ... }
export interface VoucherBatch { id, batch_code, hotspot_profile_id, router_id, quantity, prefix?, price_per_voucher?, time_limit?, data_limit_mb?, validity_days?, status, ... }
export interface HotspotActiveSession { id, router_id, router_name?, username, mac_address?, ip_address?, uptime, bytes_in, bytes_out, ... }
export interface HotspotSessionHistory { id, hotspot_user_id?, router_id, username, mac_address?, ip_address?, uptime_seconds, bytes_in, bytes_out, started_at, ended_at, ... }
export interface HotspotSyncResult { router_id, router_name, status, total_users_in_db, total_users_on_router, discrepancies[], checked_at? }
export interface HotspotSyncLog { id, router_id, router_name?, hotspot_user_id?, username?, action, status, message, diff_payload?, created_at }
```

### 4.3 React Query Hooks (`api/useHotspot.ts`)

Following the exact pattern from PPPoE's `usePppoe.ts`:
- `useHotspotUsers(filters)` — Paginated user list with auto-refresh
- `useHotspotUser(id)` — Single user detail
- `useHotspotProfiles(filters)` — Profile listing
- `useHotspotProfile(id)` — Single profile
- `useVouchers(filters)` — Voucher listing
- `useVoucherBatches(page)` — Batch listing
- `useActiveSessions(routerId?)` — Live polling every 15s
- `useSessionHistory(page, userId?, routerId?)` — History listing
- `useHotspotSyncLogs(limit, routerId?)` — Sync log listing
- `useCreateHotspotUser()` — Mutation with cache invalidation
- `useUpdateHotspotUser()` — Mutation
- `useDeleteHotspotUser()` — Mutation
- `useGenerateVoucherBatch()` — Mutation
- All mutations invalidate relevant query keys on success

### 4.4 Routes (`routes.tsx`)

```
/hotspot/                          → HotspotUsersPage       [hotspot.view]
/hotspot/users/new                 → CreateUserPage         [hotspot.create]
/hotspot/users/:id                 → UserDetailsPage        [hotspot.view]
/hotspot/users/:id/edit            → EditUserPage           [hotspot.update]
/hotspot/users/import              → ImportUsersPage        [hotspot.sync]
/hotspot/profiles                  → HotspotProfilesPage    [hotspot.view]
/hotspot/profiles/new              → CreateProfilePage      [hotspot.create]
/hotspot/profiles/:id/edit         → EditProfilePage        [hotspot.update]
/hotspot/vouchers                  → VouchersPage           [hotspot.vouchers]
/hotspot/vouchers/generate         → GenerateVouchersPage   [hotspot.vouchers]
/hotspot/sessions/active           → ActiveSessionsPage     [hotspot.monitor]
/hotspot/sync                      → SynchronizationPage    [hotspot.sync]
/hotspot/sync/import-profiles      → ImportProfilesPage     [hotspot.sync]
```

Registered in `frontend/src/routes/index.tsx`:
```tsx
<Route path="/hotspot/*" element={<HotspotRoutes />} />
```

### 4.5 Dashboard Widgets

Added to `DashboardService::networkWidgets()`:
- **Online Hotspot Users** — Count of active hotspot users across routers
- **Active Vouchers** — Count of vouchers with status 'new'
- **Expired Vouchers** — Count of expired vouchers this month
- **Daily Logins** — Count from `hotspot_login_history` for last 24h
- **Hotspot Traffic** — Aggregate bytes from active sessions
- **Synchronization Status** — Count of out-of-sync hotspot users

---

## 5. Implementation Order

### Phase 1: Backend Foundation (Core)
1. Database migration SQL for all 7 tables
2. Domain Models (HotspotUser, HotspotProfile, Voucher, VoucherBatch, HotspotActiveSession, HotspotSessionHistory, HotspotSyncResult)
3. DTOs (all data transfer objects)
4. Validators (HotspotUserValidator, HotspotProfileValidator, VoucherValidator)
5. Repository Contracts (all 7 interfaces)
6. Repository Implementations (all 7 PDO implementations)

### Phase 2: Backend Services
7. HotspotUserService — CRUD + MikroTik push
8. HotspotProfileService — CRUD + router profile fetch
9. VoucherService — Generation, redemption, stats
10. HotspotSessionMonitorService — Active sessions, disconnect, history
11. HotspotSyncService — Full sync audit, repair, import

### Phase 3: Backend Controllers & Routes
12. All 5 controllers (User, Profile, Voucher, Session, Sync)
13. Route registration file
14. Container.php DI bindings

### Phase 4: Frontend Foundation
15. types.ts + schemas.ts
16. api/useHotspot.ts (all hooks)
17. Core components (tables, forms, badges, selectors)

### Phase 5: Frontend Pages
18. HotspotUsersPage + CRUD pages
19. HotspotProfilesPage + CRUD pages
20. VouchersPage + GenerateVouchersPage
21. ActiveSessionsPage
22. SynchronizationPage + Import pages
23. UserDetailsPage (with stats, sessions)

### Phase 6: Integration
24. Dashboard widget additions
25. Route registration in AppRoutes
26. RBAC permission seeding data

---

## 6. File Count Summary

| Layer | Files |
|---|---|
| Backend Contracts | 9 |
| Backend Controllers | 5 |
| Backend DTOs | 11 |
| Backend Domain Models | 8 |
| Backend Repositories | 6 |
| Backend Services | 5 |
| Backend Validators | 3 |
| Backend Routes | 1 |
| Container modifications | 1 |
| Dashboard modifications | 1 |
| Frontend API | 1 |
| Frontend Components | 16 |
| Frontend Pages | 13 |
| Frontend Types/Schemas/Routes | 3 |
| Route registration (frontend) | 1 |
| **Total** | **~84 files** |

---

## 7. Key Design Decisions

1. **Voucher users are NOT linked to customers by default.** Hotspot vouchers are for transient public access. The `customer_id` on `hotspot_users` is nullable.
2. **Voucher codes use an ambiguous-character-free alphabet** (no I, O, 0, 1) for print readability.
3. **Profiles are router-scoped.** Each profile is tied to a specific router, matching MikroTik's model where profiles exist per-router.
4. **Sync follows PPPoE pattern exactly.** Same discrepancy detection (missing_on_router, orphan_on_router, conflict), same repair workflow, same sync log structure.
5. **No separate MikroTik API implementation.** All router communication goes through the existing `MikrotikConnectionPoolContract` and `RouterServiceContract`.
6. **Session history is logged to DB** for historical analysis, separate from live polling of `/ip/hotspot/active/print`.
7. **Soft deletes on all primary entities** (users, profiles, batches, vouchers) per Document 10 conventions.

---

**Awaiting approval to begin implementation.**
