-- Vendor & Supplier Management module schema.
-- Apply after Purchasing schema (2026_07_31_000000_create_purchasing_tables.sql).

-- 1. Expand vendors table with full Supplier Information & Categorization
ALTER TABLE vendors
    ADD COLUMN registration_number VARCHAR(100) NULL AFTER tax_id,
    ADD COLUMN address VARCHAR(255) NULL AFTER registration_number,
    ADD COLUMN city VARCHAR(100) NULL AFTER address,
    ADD COLUMN country VARCHAR(100) NOT NULL DEFAULT 'Pakistan' AFTER city,
    ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'PKR' AFTER payment_terms,
    ADD COLUMN category VARCHAR(100) NOT NULL DEFAULT 'hardware' AFTER currency,
    ADD COLUMN overall_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER category;

-- 2. Supplier Contacts Table
CREATE TABLE vendor_contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    department VARCHAR(100) NULL,
    position VARCHAR(100) NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    is_emergency BOOLEAN NOT NULL DEFAULT FALSE,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_vendor_contacts_vendor (vendor_id),
    KEY idx_vendor_contacts_primary (vendor_id, is_primary),
    CONSTRAINT fk_vendor_contacts_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_contacts_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_contacts_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Supplier Contracts Table
CREATE TABLE vendor_contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    contract_number VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    renewal_date DATE NULL,
    contract_value DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    status ENUM('draft', 'active', 'expiring', 'expired', 'terminated') NOT NULL DEFAULT 'active',
    attachment_path VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_vendor_contracts_number (contract_number),
    KEY idx_vendor_contracts_vendor (vendor_id),
    KEY idx_vendor_contracts_status (status),
    KEY idx_vendor_contracts_end_date (end_date),
    CONSTRAINT fk_vendor_contracts_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_contracts_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_contracts_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Supplier Quotations & RFQs
CREATE TABLE vendor_quotations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    purchase_request_id BIGINT UNSIGNED NULL,
    rfq_number VARCHAR(80) NULL,
    quotation_number VARCHAR(80) NOT NULL,
    quotation_date DATE NOT NULL,
    validity_date DATE NOT NULL,
    total_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    status ENUM('received', 'under_review', 'accepted', 'rejected', 'expired') NOT NULL DEFAULT 'received',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_vendor_quotations_num (quotation_number, vendor_id),
    KEY idx_vendor_quotations_vendor (vendor_id),
    KEY idx_vendor_quotations_pr (purchase_request_id),
    KEY idx_vendor_quotations_rfq (rfq_number),
    KEY idx_vendor_quotations_status (status),
    CONSTRAINT fk_vendor_quotations_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_quotations_pr FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_quotations_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_quotations_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE vendor_quotation_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quotation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    unit_price DECIMAL(15,4) NOT NULL,
    line_total DECIMAL(15,4) NOT NULL,
    notes VARCHAR(500) NULL,
    PRIMARY KEY (id),
    KEY idx_vqi_quotation (quotation_id),
    KEY idx_vqi_product (product_id),
    CONSTRAINT fk_vqi_quotation FOREIGN KEY (quotation_id) REFERENCES vendor_quotations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_vqi_product FOREIGN KEY (product_id) REFERENCES inventory_products (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Supplier Ratings & Performance
CREATE TABLE vendor_ratings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    evaluation_date DATE NOT NULL,
    delivery_performance DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    order_completion DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    product_quality DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    return_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    average_lead_time_days INT UNSIGNED NOT NULL DEFAULT 7,
    overall_score DECIMAL(3,2) NOT NULL DEFAULT 5.00,
    evaluator_user_id BIGINT UNSIGNED NOT NULL,
    comments TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_vendor_ratings_vendor (vendor_id),
    CONSTRAINT fk_vendor_ratings_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_vendor_ratings_evaluator FOREIGN KEY (evaluator_user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Views/Aliases for Supplier terminology compatibility
CREATE OR REPLACE VIEW suppliers AS SELECT * FROM vendors;
CREATE OR REPLACE VIEW supplier_contacts AS SELECT * FROM vendor_contacts;
CREATE OR REPLACE VIEW supplier_contracts AS SELECT * FROM vendor_contracts;
CREATE OR REPLACE VIEW supplier_quotations AS SELECT * FROM vendor_quotations;
CREATE OR REPLACE VIEW supplier_ratings AS SELECT * FROM vendor_ratings;

-- 7. Insert permissions
INSERT INTO permissions (name, description) VALUES
('vendors.view', 'View supplier list, details, contacts, contracts, quotations, performance, and dashboard.'),
('vendors.create', 'Create new suppliers, contacts, contracts, quotations, and performance ratings.'),
('vendors.update', 'Edit supplier information, contacts, contracts, and quotation status.'),
('vendors.delete', 'Archive suppliers and soft-delete contacts, contracts, and quotations.'),
('vendors.contracts', 'Specific access to view and manage contracts and sensitive financial terms.'),
('vendors.manage', 'Activate or archive suppliers, manage categories, and configure ratings.')
ON DUPLICATE KEY UPDATE description = VALUES(description);
