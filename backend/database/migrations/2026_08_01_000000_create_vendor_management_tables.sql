-- Vendor & Supplier Management module schema.
-- Apply after 2026_07_31_000000_create_purchasing_tables.sql.
-- The existing `vendors` table remains the canonical supplier table because
-- Inventory and Purchasing already reference it through foreign keys.

ALTER TABLE vendors
    MODIFY COLUMN status ENUM('active', 'inactive', 'on_hold', 'archived') NOT NULL DEFAULT 'active',
    ADD COLUMN registration_number VARCHAR(100) NULL AFTER tax_id,
    ADD COLUMN address VARCHAR(500) NULL AFTER registration_number,
    ADD COLUMN city VARCHAR(120) NULL AFTER address,
    ADD COLUMN country VARCHAR(120) NULL AFTER city,
    ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'PKR' AFTER payment_terms,
    ADD KEY idx_vendors_country (country),
    ADD KEY idx_vendors_email (email),
    ADD KEY idx_vendors_status_deleted (status, deleted_at);

CREATE TABLE supplier_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(1000) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_supplier_categories_code (code),
    UNIQUE KEY uk_supplier_categories_name (name),
    KEY idx_supplier_categories_status (status, deleted_at),
    CONSTRAINT fk_supplier_categories_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_categories_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_category_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    supplier_category_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_supplier_category_assignment (vendor_id, supplier_category_id),
    KEY idx_supplier_category_assignments_category (supplier_category_id, deleted_at),
    KEY idx_supplier_category_assignments_vendor (vendor_id, deleted_at),
    CONSTRAINT fk_supplier_category_assignments_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_category_assignments_category FOREIGN KEY (supplier_category_id) REFERENCES supplier_categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_category_assignments_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_category_assignments_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_contacts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    department VARCHAR(120) NULL,
    job_title VARCHAR(120) NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    is_emergency TINYINT(1) NOT NULL DEFAULT 0,
    notes VARCHAR(1000) NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_supplier_contacts_vendor (vendor_id, deleted_at),
    KEY idx_supplier_contacts_department (department),
    KEY idx_supplier_contacts_flags (vendor_id, is_primary, is_emergency, deleted_at),
    KEY idx_supplier_contacts_email (email),
    CONSTRAINT fk_supplier_contacts_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_contacts_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_contacts_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preserve existing vendor contact data as the initial primary contact.
INSERT INTO supplier_contacts (
    vendor_id, name, phone, email, is_primary, is_emergency, created_by, created_at, updated_at
)
SELECT
    id, COALESCE(NULLIF(contact_name, ''), name), phone, email, 1, 0, created_by, created_at, updated_at
FROM vendors
WHERE deleted_at IS NULL
  AND (NULLIF(contact_name, '') IS NOT NULL OR NULLIF(phone, '') IS NOT NULL OR NULLIF(email, '') IS NOT NULL);

CREATE TABLE supplier_contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    contract_number VARCHAR(80) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    renewal_date DATE NULL,
    contract_value DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    status ENUM('draft', 'active', 'expired', 'terminated', 'renewed') NOT NULL DEFAULT 'draft',
    attachment_name VARCHAR(255) NULL,
    attachment_reference VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_supplier_contracts_number (contract_number),
    KEY idx_supplier_contracts_vendor_status (vendor_id, status, deleted_at),
    KEY idx_supplier_contracts_end_date (end_date, status, deleted_at),
    KEY idx_supplier_contracts_renewal_date (renewal_date, status, deleted_at),
    CONSTRAINT fk_supplier_contracts_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_contracts_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_contracts_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_supplier_contract_dates CHECK (end_date >= start_date),
    CONSTRAINT chk_supplier_contract_value CHECK (contract_value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_quotations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    quotation_number VARCHAR(100) NOT NULL,
    rfq_reference VARCHAR(100) NULL,
    quotation_date DATE NOT NULL,
    valid_until DATE NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    subtotal DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    total_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    status ENUM('draft', 'received', 'under_review', 'accepted', 'rejected', 'expired') NOT NULL DEFAULT 'received',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_supplier_quotations_vendor_number (vendor_id, quotation_number),
    KEY idx_supplier_quotations_vendor (vendor_id, quotation_date, deleted_at),
    KEY idx_supplier_quotations_rfq (rfq_reference, currency, deleted_at),
    KEY idx_supplier_quotations_status_validity (status, valid_until, deleted_at),
    CONSTRAINT fk_supplier_quotations_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_quotations_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_quotations_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_supplier_quotation_dates CHECK (valid_until IS NULL OR valid_until >= quotation_date),
    CONSTRAINT chk_supplier_quotation_amounts CHECK (subtotal >= 0 AND tax_amount >= 0 AND total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_quotation_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    supplier_quotation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    description VARCHAR(500) NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    unit_price DECIMAL(15,4) NOT NULL,
    line_total DECIMAL(15,4) NOT NULL,
    lead_time_days SMALLINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_supplier_quotation_items_quotation (supplier_quotation_id, deleted_at),
    KEY idx_supplier_quotation_items_product (product_id, deleted_at),
    CONSTRAINT fk_supplier_quotation_items_quotation FOREIGN KEY (supplier_quotation_id) REFERENCES supplier_quotations (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_quotation_items_product FOREIGN KEY (product_id) REFERENCES inventory_products (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_quotation_items_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_quotation_items_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_supplier_quotation_item_quantity CHECK (quantity > 0),
    CONSTRAINT chk_supplier_quotation_item_prices CHECK (unit_price >= 0 AND line_total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_ratings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    vendor_id BIGINT UNSIGNED NOT NULL,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    delivery_performance_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    order_completion_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    product_quality_score DECIMAL(3,2) NOT NULL,
    return_rate_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    average_lead_time_days DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    procurement_value DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    overall_rating DECIMAL(3,2) NOT NULL,
    notes TEXT NULL,
    rated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_supplier_ratings_period (vendor_id, review_period_start, review_period_end),
    KEY idx_supplier_ratings_vendor_date (vendor_id, review_period_end, deleted_at),
    KEY idx_supplier_ratings_overall (overall_rating, deleted_at),
    CONSTRAINT fk_supplier_ratings_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_ratings_rated_by FOREIGN KEY (rated_by_user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_ratings_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_ratings_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_supplier_rating_dates CHECK (review_period_end >= review_period_start),
    CONSTRAINT chk_supplier_rating_percentages CHECK (
        delivery_performance_pct BETWEEN 0 AND 100
        AND order_completion_pct BETWEEN 0 AND 100
        AND return_rate_pct BETWEEN 0 AND 100
    ),
    CONSTRAINT chk_supplier_rating_scores CHECK (product_quality_score BETWEEN 0 AND 5 AND overall_rating BETWEEN 0 AND 5),
    CONSTRAINT chk_supplier_rating_metrics CHECK (average_lead_time_days >= 0 AND procurement_value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, description) VALUES
('vendors.view', 'View supplier dashboards, records, contacts, contracts, quotations, and performance.'),
('vendors.create', 'Create suppliers, supplier contacts, and quotations.'),
('vendors.update', 'Update suppliers, supplier contacts, and quotations.'),
('vendors.delete', 'Archive suppliers and soft-delete eligible supplier records.'),
('vendors.contracts', 'Manage supplier contracts and renewal information.'),
('vendors.manage', 'Activate suppliers and manage categories, ratings, and supplier status.')
ON DUPLICATE KEY UPDATE description = VALUES(description);
