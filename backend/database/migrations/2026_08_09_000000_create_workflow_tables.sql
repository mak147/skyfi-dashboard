-- Workflow Automation Engine
CREATE TABLE workflows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(180) NOT NULL,
    description TEXT NULL,
    status ENUM('draft', 'active', 'paused', 'disabled') NOT NULL DEFAULT 'draft',
    is_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    active_version_id BIGINT UNSIGNED NULL,
    trigger_event_key VARCHAR(180) NULL,
    schedule_mode ENUM('immediate', 'delayed', 'cron', 'recurring') NOT NULL DEFAULT 'immediate',
    cron_expression VARCHAR(80) NULL,
    delay_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    max_retries TINYINT UNSIGNED NOT NULL DEFAULT 0,
    retry_delay_seconds INT UNSIGNED NOT NULL DEFAULT 60,
    last_executed_at TIMESTAMP NULL,
    execution_count INT UNSIGNED NOT NULL DEFAULT 0,
    success_count INT UNSIGNED NOT NULL DEFAULT 0,
    failure_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uq_workflows_uuid (uuid),
    INDEX idx_workflows_trigger (is_enabled, status, trigger_event_key),
    INDEX idx_workflows_status (status),
    INDEX idx_workflows_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workflow_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    definition JSON NOT NULL,
    changelog VARCHAR(500) NULL,
    is_published BOOLEAN NOT NULL DEFAULT TRUE,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_workflow_versions_workflow FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    UNIQUE KEY uq_workflow_versions (workflow_id, version_number),
    INDEX idx_workflow_versions_workflow (workflow_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workflow_triggers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    version_id BIGINT UNSIGNED NOT NULL,
    event_key VARCHAR(180) NOT NULL,
    source_module VARCHAR(80) NOT NULL DEFAULT 'system',
    filter_json JSON NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_workflow_triggers_workflow FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_triggers_version FOREIGN KEY (version_id) REFERENCES workflow_versions(id) ON DELETE CASCADE,
    INDEX idx_workflow_triggers_event (event_key, is_active),
    INDEX idx_workflow_triggers_workflow (workflow_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workflow_conditions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    version_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    group_logic ENUM('AND', 'OR') NULL,
    field_path VARCHAR(180) NULL,
    operator ENUM(
        'equals', 'not_equals', 'contains', 'starts_with', 'ends_with',
        'greater_than', 'less_than', 'between', 'is_empty', 'is_not_empty'
    ) NULL,
    value_json JSON NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_workflow_conditions_workflow FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_conditions_version FOREIGN KEY (version_id) REFERENCES workflow_versions(id) ON DELETE CASCADE,
    INDEX idx_workflow_conditions_version (version_id),
    INDEX idx_workflow_conditions_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workflow_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id BIGINT UNSIGNED NOT NULL,
    version_id BIGINT UNSIGNED NOT NULL,
    action_type VARCHAR(80) NOT NULL,
    name VARCHAR(180) NULL,
    config_json JSON NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    continue_on_failure BOOLEAN NOT NULL DEFAULT FALSE,
    is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_workflow_actions_workflow FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_actions_version FOREIGN KEY (version_id) REFERENCES workflow_versions(id) ON DELETE CASCADE,
    INDEX idx_workflow_actions_version (version_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workflow_executions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    workflow_id BIGINT UNSIGNED NOT NULL,
    version_id BIGINT UNSIGNED NOT NULL,
    trigger_event_key VARCHAR(180) NULL,
    trigger_payload JSON NULL,
    trigger_source ENUM('event', 'manual', 'test', 'schedule', 'recurring') NOT NULL DEFAULT 'event',
    status ENUM(
        'pending', 'scheduled', 'running', 'success', 'failed',
        'partial', 'skipped', 'cancelled', 'paused'
    ) NOT NULL DEFAULT 'pending',
    scheduled_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    duration_ms INT UNSIGNED NULL,
    attempt_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 1,
    next_retry_at TIMESTAMP NULL,
    result_json JSON NULL,
    action_results JSON NULL,
    error_message TEXT NULL,
    actor_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_workflow_executions_uuid (uuid),
    CONSTRAINT fk_workflow_executions_workflow FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    CONSTRAINT fk_workflow_executions_version FOREIGN KEY (version_id) REFERENCES workflow_versions(id) ON DELETE CASCADE,
    INDEX idx_workflow_executions_workflow (workflow_id, created_at),
    INDEX idx_workflow_executions_status_sched (status, scheduled_at),
    INDEX idx_workflow_executions_retry (status, next_retry_at),
    INDEX idx_workflow_executions_event (trigger_event_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, description) VALUES
('workflow.view', 'View workflows, catalogs, and execution history.'),
('workflow.create', 'Create and clone workflows.'),
('workflow.update', 'Update, enable, disable, and version workflows.'),
('workflow.execute', 'Manually run, test, and retry workflow executions.'),
('workflow.manage', 'Pause, resume, cancel, and administer workflow automation.');

INSERT INTO permission_role (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name LIKE 'workflow.%';
