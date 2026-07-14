-- Billing Management module schema
-- Apply through the project's migration runner; do not alter production manually.

CREATE TABLE invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    connection_id BIGINT UNSIGNED NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,
    status ENUM('draft','pending','issued','partially_paid','paid','overdue','cancelled','void') NOT NULL DEFAULT 'draft',
    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    late_fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    previous_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_invoices_number (invoice_number),
    KEY idx_invoices_customer_id_status (customer_id, status),
    KEY idx_invoices_status_due_date (status, due_date),
    KEY idx_invoices_issue_date (issue_date),
    KEY idx_invoices_connection_id (connection_id),
    KEY idx_invoices_package_id (package_id),
    KEY idx_invoices_deleted_at (deleted_at),
    CONSTRAINT fk_invoices_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_connection FOREIGN KEY (connection_id) REFERENCES connections (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_package FOREIGN KEY (package_id) REFERENCES packages (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoice_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('recurring','one_time','installation','prorated','late_fee','discount','tax','custom') NOT NULL DEFAULT 'recurring',
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_invoice_items_invoice_id (invoice_id),
    CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE billing_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    connection_id BIGINT UNSIGNED NOT NULL,
    billing_cycle ENUM('monthly','quarterly','semi_annual','annual','custom') NOT NULL DEFAULT 'monthly',
    custom_interval_days INT UNSIGNED NULL,
    anchor_date DATE NOT NULL,
    next_bill_date DATE NOT NULL,
    grace_period_days INT UNSIGNED NOT NULL DEFAULT 0,
    auto_generate TINYINT(1) NOT NULL DEFAULT 1,
    proration_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_billing_schedules_connection (connection_id),
    KEY idx_billing_schedules_next_bill_date (next_bill_date),
    KEY idx_billing_schedules_auto_generate (auto_generate, next_bill_date),
    CONSTRAINT fk_billing_schedules_connection FOREIGN KEY (connection_id) REFERENCES connections (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE late_fee_rules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    days_after_due INT UNSIGNED NOT NULL,
    fee_type ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed',
    fee_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_late_fee_rules_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoice_activities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT NULL,
    performed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_invoice_activities_invoice_id (invoice_id),
    CONSTRAINT fk_invoice_activities_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_invoice_activities_performed_by FOREIGN KEY (performed_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Billing Permissions
INSERT INTO permissions (name, description) VALUES
('billing.view', 'View invoices, billing schedules, and billing history.'),
('billing.create', 'Create manual invoices.'),
('billing.update', 'Update draft and pending invoices.'),
('billing.delete', 'Soft-delete invoice records.'),
('billing.generate', 'Generate single and bulk invoices.'),
('billing.export', 'Export invoice data.'),
('billing.manage', 'Manage invoice status, billing schedules, and late fee rules.');
