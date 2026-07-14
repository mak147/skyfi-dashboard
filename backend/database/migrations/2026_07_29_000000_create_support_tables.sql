-- SkyFi Support Ticket & Helpdesk module
-- Normalized ticket, SLA, assignment, comment, and immutable history schema.

CREATE TABLE support_teams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_support_teams_name (name),
    KEY idx_support_teams_active (is_active, deleted_at),
    CONSTRAINT fk_support_teams_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_teams_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_team_members (
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_team_lead TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (team_id, user_id),
    KEY idx_support_team_members_user (user_id),
    CONSTRAINT fk_support_team_members_team FOREIGN KEY (team_id) REFERENCES support_teams (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_support_team_members_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_support_team_members_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description VARCHAR(500) NULL,
    default_team_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_ticket_categories_slug (slug),
    KEY idx_ticket_categories_active (is_active, deleted_at),
    CONSTRAINT fk_ticket_categories_team FOREIGN KEY (default_team_id) REFERENCES support_teams (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_categories_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_categories_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sla_policies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL,
    response_minutes INT UNSIGNED NOT NULL,
    resolution_minutes INT UNSIGNED NOT NULL,
    escalation_after_minutes INT UNSIGNED NOT NULL,
    pause_while_waiting_customer TINYINT(1) NOT NULL DEFAULT 1,
    escalation_team_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    KEY idx_sla_policy_match (category_id, priority, is_active, deleted_at),
    CONSTRAINT fk_sla_policies_category FOREIGN KEY (category_id) REFERENCES ticket_categories (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sla_policies_team FOREIGN KEY (escalation_team_id) REFERENCES support_teams (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sla_policies_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sla_policies_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_sla_response_positive CHECK (response_minutes > 0),
    CONSTRAINT chk_sla_resolution_positive CHECK (resolution_minutes >= response_minutes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_tickets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(24) NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    connection_id BIGINT UNSIGNED NULL,
    package_id BIGINT UNSIGNED NULL,
    pppoe_account_id BIGINT UNSIGNED NULL,
    hotspot_user_id BIGINT UNSIGNED NULL,
    router_id BIGINT UNSIGNED NULL,
    network_device_id BIGINT UNSIGNED NULL,
    monitoring_alert_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    sla_policy_id BIGINT UNSIGNED NOT NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    status ENUM('new','open','assigned','in_progress','waiting_customer','escalated','resolved','closed','cancelled') NOT NULL DEFAULT 'new',
    source ENUM('portal','email','phone','staff','system','monitoring') NOT NULL DEFAULT 'staff',
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    resolution TEXT NULL,
    root_cause TEXT NULL,
    parent_ticket_id BIGINT UNSIGNED NULL,
    merged_into_ticket_id BIGINT UNSIGNED NULL,
    first_response_due_at DATETIME NOT NULL,
    resolution_due_at DATETIME NOT NULL,
    first_responded_at DATETIME NULL,
    response_breached_at DATETIME NULL,
    resolution_breached_at DATETIME NULL,
    waiting_started_at DATETIME NULL,
    sla_paused_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    escalation_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
    escalated_at DATETIME NULL,
    resolved_at DATETIME NULL,
    closed_at DATETIME NULL,
    closed_by BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_support_tickets_number (ticket_number),
    KEY idx_support_queue (status, priority, created_at),
    KEY idx_support_customer (customer_id, status),
    KEY idx_support_connection (connection_id),
    KEY idx_support_category (category_id, status),
    KEY idx_support_response_sla (first_response_due_at, first_responded_at, response_breached_at),
    KEY idx_support_resolution_sla (resolution_due_at, status, resolution_breached_at),
    KEY idx_support_integrations (router_id, network_device_id, monitoring_alert_id),
    KEY idx_support_parent (parent_ticket_id),
    KEY idx_support_merged (merged_into_ticket_id),
    CONSTRAINT fk_support_ticket_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_connection FOREIGN KEY (connection_id) REFERENCES connections (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_pppoe FOREIGN KEY (pppoe_account_id) REFERENCES pppoe_accounts (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_hotspot FOREIGN KEY (hotspot_user_id) REFERENCES hotspot_users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_router FOREIGN KEY (router_id) REFERENCES mikrotik_routers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_device FOREIGN KEY (network_device_id) REFERENCES network_devices (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_alert FOREIGN KEY (monitoring_alert_id) REFERENCES monitoring_alerts (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_category FOREIGN KEY (category_id) REFERENCES ticket_categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_sla FOREIGN KEY (sla_policy_id) REFERENCES sla_policies (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_parent FOREIGN KEY (parent_ticket_id) REFERENCES support_tickets (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_merged FOREIGN KEY (merged_into_ticket_id) REFERENCES support_tickets (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_closed_by FOREIGN KEY (closed_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_support_ticket_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_comments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    type ENUM('internal_note','customer_reply','staff_reply') NOT NULL,
    body TEXT NOT NULL,
    author_user_id BIGINT UNSIGNED NULL,
    author_customer_id BIGINT UNSIGNED NULL,
    is_edited TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    KEY idx_ticket_comments_timeline (ticket_id, created_at),
    CONSTRAINT fk_ticket_comments_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_comments_user FOREIGN KEY (author_user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_comments_customer FOREIGN KEY (author_customer_id) REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_comments_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_comments_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_ticket_comment_author CHECK (author_user_id IS NOT NULL OR author_customer_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    staff_user_id BIGINT UNSIGNED NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,
    assignment_reason VARCHAR(500) NULL,
    assigned_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    ended_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ticket_assignment_active (ticket_id, ended_at),
    KEY idx_ticket_assignment_staff (staff_user_id, ended_at),
    KEY idx_ticket_assignment_team (team_id, ended_at),
    CONSTRAINT fk_ticket_assignment_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_assignment_team FOREIGN KEY (team_id) REFERENCES support_teams (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_assignment_staff FOREIGN KEY (staff_user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_assignment_by FOREIGN KEY (assigned_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_assignment_ended_by FOREIGN KEY (ended_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_ticket_assignment_target CHECK (team_id IS NOT NULL OR staff_user_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ticket_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    old_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NULL,
    field_name VARCHAR(100) NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    description VARCHAR(1000) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ticket_history_timeline (ticket_id, created_at),
    KEY idx_ticket_history_type (event_type, created_at),
    CONSTRAINT fk_ticket_history_ticket FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_history_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO support_teams (name, description) VALUES
('Customer Support L1', 'First-line customer support and triage'),
('Network Operations', 'Network incidents, outages, and advanced diagnostics'),
('Field Support', 'On-site technical support and infrastructure incidents')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO ticket_categories (name, slug, description) VALUES
('Connectivity', 'connectivity', 'General internet connectivity issues'),
('Network Outage', 'network-outage', 'Area, tower, sector, or router outages'),
('PPPoE', 'pppoe', 'PPPoE authentication and session issues'),
('Hotspot', 'hotspot', 'Hotspot login, voucher, and session issues'),
('Equipment / Router', 'equipment-router', 'Customer or network equipment issues'),
('Installation', 'installation', 'Installation and activation assistance'),
('Billing', 'billing', 'Billing and payment support'),
('Account', 'account', 'Customer account assistance'),
('General Support', 'general-support', 'General support requests')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description);

INSERT INTO sla_policies (name, priority, response_minutes, resolution_minutes, escalation_after_minutes) VALUES
('Low Priority Standard', 'low', 480, 2880, 1440),
('Normal Priority Standard', 'normal', 240, 1440, 720),
('High Priority Standard', 'high', 60, 480, 240),
('Urgent Priority Standard', 'urgent', 15, 120, 60);

INSERT INTO permissions (name, description) VALUES
('support.view', 'View support tickets, timelines, dashboards, and SLA performance'),
('support.create', 'Create support tickets'),
('support.update', 'Edit support tickets and add replies or internal notes'),
('support.assign', 'Assign and reassign support tickets'),
('support.close', 'Resolve, close, and reopen support tickets'),
('support.manage', 'Escalate, merge, split, cancel, delete, and configure support resources')
ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = CURRENT_TIMESTAMP;

INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.name = 'Super Administrator' WHERE p.name LIKE 'support.%';

INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.name = 'Customer Support'
WHERE p.name IN ('support.view','support.create','support.update','support.assign','support.close');
