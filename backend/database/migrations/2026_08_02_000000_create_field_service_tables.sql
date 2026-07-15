-- Customer Installation & Field Service Management
-- Apply after vendor management migrations.

CREATE TABLE field_teams (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_field_teams_code (code),
    KEY idx_field_teams_status (status, deleted_at),
    CONSTRAINT fk_field_teams_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_field_teams_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technicians (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    field_team_id BIGINT UNSIGNED NULL,
    employee_code VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NULL,
    status ENUM('active','inactive','on_leave') NOT NULL DEFAULT 'active',
    max_daily_jobs TINYINT UNSIGNED NOT NULL DEFAULT 6,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_technicians_user (user_id),
    UNIQUE KEY uk_technicians_employee_code (employee_code),
    KEY idx_technicians_team_status (field_team_id, status, deleted_at),
    CONSTRAINT fk_technicians_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_technicians_team FOREIGN KEY (field_team_id) REFERENCES field_teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_technicians_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_technicians_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE field_team_members (
    field_team_id BIGINT UNSIGNED NOT NULL,
    technician_id BIGINT UNSIGNED NOT NULL,
    is_team_lead TINYINT(1) NOT NULL DEFAULT 0,
    joined_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    PRIMARY KEY (field_team_id, technician_id),
    KEY idx_field_team_members_technician (technician_id, ended_at),
    CONSTRAINT fk_field_team_members_team FOREIGN KEY (field_team_id) REFERENCES field_teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_field_team_members_technician FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technician_skills (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    technician_id BIGINT UNSIGNED NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    proficiency ENUM('basic','intermediate','advanced','expert') NOT NULL DEFAULT 'basic',
    certified_at DATE NULL,
    expires_at DATE NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_technician_skill (technician_id, skill_name),
    KEY idx_technician_skills_name (skill_name, deleted_at),
    CONSTRAINT fk_technician_skills_technician FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
    CONSTRAINT fk_technician_skills_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_technician_skills_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_technician_skill_dates CHECK (expires_at IS NULL OR certified_at IS NULL OR expires_at >= certified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technician_service_areas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    technician_id BIGINT UNSIGNED NOT NULL,
    city VARCHAR(100) NOT NULL,
    area VARCHAR(100) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_technician_service_area (technician_id, city, area),
    KEY idx_technician_service_areas (city, area, deleted_at),
    CONSTRAINT fk_technician_service_areas_technician FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
    CONSTRAINT fk_technician_service_areas_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_technician_service_areas_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technician_availability (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    technician_id BIGINT UNSIGNED NOT NULL,
    availability_type ENUM('available','unavailable','leave') NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    reason VARCHAR(500) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    KEY idx_technician_availability_range (technician_id, starts_at, ends_at, deleted_at),
    CONSTRAINT fk_technician_availability_technician FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
    CONSTRAINT fk_technician_availability_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_technician_availability_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_technician_availability_range CHECK (ends_at > starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE installation_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    connection_id BIGINT UNSIGNED NOT NULL,
    assigned_technician_id BIGINT UNSIGNED NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    status ENUM('pending','assigned','scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    source ENUM('manual','connection_approval','system') NOT NULL DEFAULT 'manual',
    preferred_start_at DATETIME NULL,
    preferred_end_at DATETIME NULL,
    scheduled_start_at DATETIME NULL,
    scheduled_end_at DATETIME NULL,
    service_address VARCHAR(500) NOT NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    notes TEXT NULL,
    cancellation_reason VARCHAR(1000) NULL,
    completed_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_installation_requests_number (request_number),
    UNIQUE KEY uk_installation_requests_connection (connection_id),
    KEY idx_installation_requests_queue (status, priority, created_at),
    KEY idx_installation_requests_customer (customer_id, status),
    KEY idx_installation_requests_schedule (assigned_technician_id, scheduled_start_at, scheduled_end_at),
    CONSTRAINT fk_installation_requests_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_installation_requests_connection FOREIGN KEY (connection_id) REFERENCES connections(id) ON DELETE RESTRICT,
    CONSTRAINT fk_installation_requests_technician FOREIGN KEY (assigned_technician_id) REFERENCES technicians(id) ON DELETE SET NULL,
    CONSTRAINT fk_installation_requests_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_installation_requests_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_installation_request_preferred CHECK (preferred_end_at IS NULL OR preferred_start_at IS NULL OR preferred_end_at > preferred_start_at),
    CONSTRAINT chk_installation_request_schedule CHECK (scheduled_end_at IS NULL OR scheduled_start_at IS NULL OR scheduled_end_at > scheduled_start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    work_order_number VARCHAR(50) NOT NULL,
    installation_request_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    connection_id BIGINT UNSIGNED NULL,
    support_ticket_id BIGINT UNSIGNED NULL,
    monitoring_alert_id BIGINT UNSIGNED NULL,
    pop_site_id BIGINT UNSIGNED NULL,
    tower_id BIGINT UNSIGNED NULL,
    network_device_id BIGINT UNSIGNED NULL,
    assigned_technician_id BIGINT UNSIGNED NULL,
    field_team_id BIGINT UNSIGNED NULL,
    type ENUM('installation','maintenance','repair','equipment_replacement','relocation','site_survey','disconnection') NOT NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    status ENUM('pending','assigned','scheduled','in_progress','on_hold','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
    title VARCHAR(255) NOT NULL,
    service_address VARCHAR(500) NOT NULL,
    scheduled_start_at DATETIME NULL,
    scheduled_end_at DATETIME NULL,
    started_at DATETIME NULL,
    completed_at DATETIME NULL,
    estimated_duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 120,
    installation_charge DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    installation_charge_status ENUM('not_applicable','pending','handed_off') NOT NULL DEFAULT 'not_applicable',
    notes TEXT NULL,
    completion_notes TEXT NULL,
    failure_reason VARCHAR(1000) NULL,
    cancellation_reason VARCHAR(1000) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_work_orders_number (work_order_number),
    UNIQUE KEY uk_work_orders_installation_request (installation_request_id),
    KEY idx_work_orders_queue (status, priority, scheduled_start_at),
    KEY idx_work_orders_technician_schedule (assigned_technician_id, scheduled_start_at, scheduled_end_at),
    KEY idx_work_orders_team_schedule (field_team_id, scheduled_start_at),
    KEY idx_work_orders_customer (customer_id, created_at),
    KEY idx_work_orders_connection (connection_id),
    KEY idx_work_orders_ticket (support_ticket_id),
    KEY idx_work_orders_type_status (type, status),
    CONSTRAINT fk_work_orders_installation_request FOREIGN KEY (installation_request_id) REFERENCES installation_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
    CONSTRAINT fk_work_orders_connection FOREIGN KEY (connection_id) REFERENCES connections(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_ticket FOREIGN KEY (support_ticket_id) REFERENCES support_tickets(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_alert FOREIGN KEY (monitoring_alert_id) REFERENCES monitoring_alerts(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_pop_site FOREIGN KEY (pop_site_id) REFERENCES pop_sites(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_tower FOREIGN KEY (tower_id) REFERENCES towers(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_device FOREIGN KEY (network_device_id) REFERENCES network_devices(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_technician FOREIGN KEY (assigned_technician_id) REFERENCES technicians(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_team FOREIGN KEY (field_team_id) REFERENCES field_teams(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_orders_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_work_orders_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_work_order_schedule CHECK (scheduled_end_at IS NULL OR scheduled_start_at IS NULL OR scheduled_end_at > scheduled_start_at),
    CONSTRAINT chk_work_order_charge CHECK (installation_charge >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE field_visits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    work_order_id BIGINT UNSIGNED NOT NULL,
    technician_id BIGINT UNSIGNED NOT NULL,
    visit_number SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('scheduled','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'scheduled',
    scheduled_start_at DATETIME NULL,
    scheduled_end_at DATETIME NULL,
    checked_in_at DATETIME NULL,
    checked_out_at DATETIME NULL,
    check_in_latitude DECIMAL(10,7) NULL,
    check_in_longitude DECIMAL(10,7) NULL,
    check_out_latitude DECIMAL(10,7) NULL,
    check_out_longitude DECIMAL(10,7) NULL,
    gps_accuracy DECIMAL(8,2) NULL,
    before_photo_reference VARCHAR(500) NULL,
    after_photo_reference VARCHAR(500) NULL,
    customer_signature_reference VARCHAR(500) NULL,
    customer_name VARCHAR(200) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY uk_field_visit_number (work_order_id, visit_number),
    KEY idx_field_visits_technician (technician_id, scheduled_start_at, status),
    CONSTRAINT fk_field_visits_work_order FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_field_visits_technician FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE RESTRICT,
    CONSTRAINT fk_field_visits_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_field_visits_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_field_visit_check_out CHECK (checked_out_at IS NULL OR checked_in_at IS NULL OR checked_out_at >= checked_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    work_order_id BIGINT UNSIGNED NOT NULL,
    field_visit_id BIGINT UNSIGNED NULL,
    technician_id BIGINT UNSIGNED NOT NULL,
    log_type ENUM('travel','diagnostic','installation','repair','maintenance','note') NOT NULL,
    started_at DATETIME NULL,
    ended_at DATETIME NULL,
    duration_minutes SMALLINT UNSIGNED NULL,
    description TEXT NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    KEY idx_work_logs_order_time (work_order_id, created_at),
    KEY idx_work_logs_technician_time (technician_id, started_at),
    CONSTRAINT fk_work_logs_work_order FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_work_logs_visit FOREIGN KEY (field_visit_id) REFERENCES field_visits(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_logs_technician FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE RESTRICT,
    CONSTRAINT fk_work_logs_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_work_logs_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_work_log_range CHECK (ended_at IS NULL OR started_at IS NULL OR ended_at >= started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_order_materials (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    work_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NULL,
    warehouse_location_id BIGINT UNSIGNED NULL,
    quantity_planned DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    quantity_used DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    unit_cost DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    usage_status ENUM('planned','reserved','used','returned','cancelled') NOT NULL DEFAULT 'planned',
    stock_movement_id BIGINT UNSIGNED NULL,
    notes VARCHAR(1000) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    KEY idx_work_order_materials_order (work_order_id, usage_status),
    KEY idx_work_order_materials_product (product_id),
    KEY idx_work_order_materials_asset (asset_id),
    CONSTRAINT fk_work_order_materials_order FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_work_order_materials_product FOREIGN KEY (product_id) REFERENCES inventory_products(id) ON DELETE RESTRICT,
    CONSTRAINT fk_work_order_materials_asset FOREIGN KEY (asset_id) REFERENCES inventory_assets(id) ON DELETE RESTRICT,
    CONSTRAINT fk_work_order_materials_location FOREIGN KEY (warehouse_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_order_materials_movement FOREIGN KEY (stock_movement_id) REFERENCES inventory_stock_movements(id) ON DELETE SET NULL,
    CONSTRAINT fk_work_order_materials_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_work_order_materials_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_work_order_material_quantities CHECK (quantity_planned >= 0 AND quantity_used >= 0),
    CONSTRAINT chk_work_order_material_cost CHECK (unit_cost >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_order_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    work_order_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(60) NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    old_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NULL,
    description VARCHAR(1000) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_work_order_history_timeline (work_order_id, created_at),
    KEY idx_work_order_history_event (event_type, created_at),
    CONSTRAINT fk_work_order_history_order FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_work_order_history_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO field_teams (code, name, description, created_by)
SELECT 'FIELD-SUPPORT', 'Field Support', 'Customer installations, repairs, and on-site maintenance.', MIN(id) FROM users
WHERE EXISTS (SELECT 1 FROM users)
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO permissions (name, description) VALUES
('field.view', 'View field-service dashboards, work orders, technicians, schedules, visits, and activity.'),
('field.create', 'Create installation requests and field-service work orders.'),
('field.update', 'Update field work, statuses, visits, logs, and material usage.'),
('field.assign', 'Assign technicians and teams and schedule or reschedule field work.'),
('field.complete', 'Complete field work and trigger inventory and activation integrations.'),
('field.manage', 'Manage technicians, availability, cancellations, and field-service administration.')
ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = CURRENT_TIMESTAMP;

INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.name IN ('Super Administrator','Regional Manager') WHERE p.name LIKE 'field.%';
INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.name = 'Installation Team / Field Technician' WHERE p.name IN ('field.view','field.update','field.complete');
INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.name = 'Customer Support' WHERE p.name IN ('field.view','field.create');
INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.name = 'Network Engineer' WHERE p.name IN ('field.view','field.create','field.update');
INSERT IGNORE INTO permission_role (permission_id, role_id)
SELECT p.id, r.id FROM permissions p JOIN roles r ON r.name = 'Inventory Manager' WHERE p.name = 'field.view';
