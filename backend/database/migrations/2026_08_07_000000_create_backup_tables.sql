-- Backup Storage Providers
CREATE TABLE backup_storage_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('local', 's3', 'ftp', 'sftp', 'nas') NOT NULL,
    config JSON NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Schedules
CREATE TABLE backup_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('database', 'files', 'config', 'full') NOT NULL,
    cron_expression VARCHAR(100) NOT NULL,
    retention_days INT UNSIGNED NOT NULL DEFAULT 30,
    storage_provider_id INT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_backup_schedules_provider FOREIGN KEY (storage_provider_id) REFERENCES backup_storage_providers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Jobs
CREATE TABLE backup_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT UNSIGNED NULL,
    type ENUM('database', 'files', 'config', 'full') NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_backup_jobs_schedule FOREIGN KEY (schedule_id) REFERENCES backup_schedules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup Files
CREATE TABLE backup_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id BIGINT UNSIGNED NOT NULL,
    storage_provider_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    checksum VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    verified_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_backup_files_job FOREIGN KEY (job_id) REFERENCES backup_jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_backup_files_provider FOREIGN KEY (storage_provider_id) REFERENCES backup_storage_providers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restore History
CREATE TABLE restore_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_file_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed', 'rolled_back') NOT NULL DEFAULT 'pending',
    target_environment VARCHAR(100) NOT NULL DEFAULT 'production',
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_restore_history_file FOREIGN KEY (backup_file_id) REFERENCES backup_files(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification History
CREATE TABLE verification_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    backup_file_id BIGINT UNSIGNED NOT NULL,
    status ENUM('success', 'failure') NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_verification_history_file FOREIGN KEY (backup_file_id) REFERENCES backup_files(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Disaster Recovery Plans
CREATE TABLE dr_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    rpo_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    rto_minutes INT UNSIGNED NOT NULL DEFAULT 240,
    content MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial Storage Provider (Local)
INSERT INTO backup_storage_providers (name, type, config, is_default) 
VALUES ('Local Storage', 'local', '{"path": "/var/backups/skyfi"}', TRUE);

-- Insert a default DR Runbook
INSERT INTO dr_plans (name, description, rpo_minutes, rto_minutes, content)
VALUES ('Standard Regional Recovery', 'Standard procedures for recovering from a full regional outage.', 60, 240, '# Disaster Recovery Runbook\n\n## Phase 1: Failover\n1. Declare disaster\n2. Promote DR Database\n3. Scale Up Application Tier\n4. Update Configuration\n\n## Phase 2: DNS Switch\n1. Verify health\n2. Update Route53\n\n## Phase 3: Restoration\n1. Cache warming\n2. Manual validation');

-- Backup Permissions
INSERT INTO permissions (name, description) VALUES 
('backup.view', 'View backup jobs and history'),
('backup.create', 'Trigger manual backups'),
('backup.restore', 'Restore data from backups'),
('backup.manage', 'Manage backup schedules and storage providers');

-- Assign to Admin Role (assuming role ID 1 is Admin)
INSERT INTO permission_role (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name LIKE 'backup.%';

