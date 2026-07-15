-- Purchasing & Procurement module schema.
-- Apply after the Inventory migration (2026_07_30_000000_create_inventory_tables.sql).

CREATE TABLE purchase_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_number VARCHAR(40) NOT NULL,
    requester_user_id BIGINT UNSIGNED NOT NULL,
    department VARCHAR(100) NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    required_date DATE NULL,
    status ENUM('draft', 'pending_approval', 'approved', 'rejected', 'cancelled', 'converted') NOT NULL DEFAULT 'draft',
    source_purchase_order_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_purchase_requests_number (request_number),
    KEY idx_purchase_requests_status (status),
    KEY idx_purchase_requests_priority (priority),
    KEY idx_purchase_requests_requester (requester_user_id),
    KEY idx_purchase_requests_required_date (required_date),
    CONSTRAINT fk_purchase_requests_requester FOREIGN KEY (requester_user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_requests_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_requests_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_request_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    purchase_request_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(500) NULL,
    quantity DECIMAL(15,4) NOT NULL,
    estimated_unit_cost DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    notes VARCHAR(1000) NULL,
    PRIMARY KEY (id),
    KEY idx_purchase_request_items_request (purchase_request_id),
    KEY idx_purchase_request_items_product (product_id),
    CONSTRAINT fk_purchase_request_items_request FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_request_items_product FOREIGN KEY (product_id) REFERENCES inventory_products (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_purchase_request_item_qty CHECK (quantity > 0),
    CONSTRAINT chk_purchase_request_item_cost CHECK (estimated_unit_cost >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_request_approvals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    purchase_request_id BIGINT UNSIGNED NOT NULL,
    approver_user_id BIGINT UNSIGNED NOT NULL,
    decision ENUM('approved', 'rejected') NOT NULL,
    comments TEXT NULL,
    decided_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_purchase_request_approvals_request (purchase_request_id),
    CONSTRAINT fk_purchase_request_approvals_request FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_request_approvals_approver FOREIGN KEY (approver_user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    po_number VARCHAR(40) NOT NULL,
    vendor_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    purchase_request_id BIGINT UNSIGNED NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    subtotal DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    total_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    order_date DATE NOT NULL,
    expected_delivery_date DATE NULL,
    delivery_date DATE NULL,
    status ENUM('draft', 'pending_approval', 'approved', 'rejected', 'sent', 'partially_received', 'fully_received', 'closed', 'cancelled') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_purchase_orders_number (po_number),
    KEY idx_purchase_orders_status (status),
    KEY idx_purchase_orders_vendor (vendor_id),
    KEY idx_purchase_orders_warehouse (warehouse_id),
    KEY idx_purchase_orders_order_date (order_date),
    KEY idx_purchase_orders_expected_delivery (expected_delivery_date),
    KEY idx_purchase_orders_request (purchase_request_id),
    CONSTRAINT fk_purchase_orders_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_orders_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_orders_request FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_orders_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_orders_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_purchase_orders_tax CHECK (tax_rate >= 0 AND tax_rate <= 100),
    CONSTRAINT chk_purchase_orders_discount CHECK (discount_amount >= 0),
    CONSTRAINT chk_purchase_orders_totals CHECK (subtotal >= 0 AND tax_amount >= 0 AND total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(500) NULL,
    quantity_ordered DECIMAL(15,4) NOT NULL,
    quantity_received DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    quantity_damaged DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    quantity_returned DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    unit_price DECIMAL(15,4) NOT NULL,
    line_total DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    notes VARCHAR(1000) NULL,
    PRIMARY KEY (id),
    KEY idx_purchase_order_items_order (purchase_order_id),
    KEY idx_purchase_order_items_product (product_id),
    CONSTRAINT fk_purchase_order_items_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_order_items_product FOREIGN KEY (product_id) REFERENCES inventory_products (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_po_item_qty_ordered CHECK (quantity_ordered > 0),
    CONSTRAINT chk_po_item_qty_received CHECK (quantity_received >= 0),
    CONSTRAINT chk_po_item_qty_damaged CHECK (quantity_damaged >= 0),
    CONSTRAINT chk_po_item_qty_returned CHECK (quantity_returned >= 0),
    CONSTRAINT chk_po_item_unit_price CHECK (unit_price >= 0),
    CONSTRAINT chk_po_item_line_total CHECK (line_total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE po_approvals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    approver_user_id BIGINT UNSIGNED NOT NULL,
    decision ENUM('approved', 'rejected') NOT NULL,
    comments TEXT NULL,
    decided_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_po_approvals_order (purchase_order_id),
    CONSTRAINT fk_po_approvals_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_po_approvals_approver FOREIGN KEY (approver_user_id) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE goods_receipts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    receipt_number VARCHAR(40) NOT NULL,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    status ENUM('received', 'partial', 'returned') NOT NULL DEFAULT 'received',
    received_by BIGINT UNSIGNED NOT NULL,
    received_at DATETIME NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_goods_receipts_number (receipt_number),
    KEY idx_goods_receipts_order (purchase_order_id),
    KEY idx_goods_receipts_warehouse (warehouse_id),
    KEY idx_goods_receipts_status (status),
    KEY idx_goods_receipts_received_at (received_at),
    CONSTRAINT fk_goods_receipts_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_goods_receipts_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouses (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_goods_receipts_received_by FOREIGN KEY (received_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE goods_receipt_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    goods_receipt_id BIGINT UNSIGNED NOT NULL,
    purchase_order_item_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_accepted DECIMAL(15,4) NOT NULL,
    quantity_damaged DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    quantity_short DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    warehouse_location_id BIGINT UNSIGNED NULL,
    condition ENUM('available', 'reserved', 'quarantine', 'damaged') NOT NULL DEFAULT 'available',
    notes VARCHAR(1000) NULL,
    PRIMARY KEY (id),
    KEY idx_goods_receipt_items_receipt (goods_receipt_id),
    KEY idx_goods_receipt_items_po_item (purchase_order_item_id),
    KEY idx_goods_receipt_items_product (product_id),
    CONSTRAINT fk_goods_receipt_items_receipt FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_goods_receipt_items_po_item FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_goods_receipt_items_product FOREIGN KEY (product_id) REFERENCES inventory_products (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_goods_receipt_items_location FOREIGN KEY (warehouse_location_id) REFERENCES warehouse_locations (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_gr_item_accepted CHECK (quantity_accepted >= 0),
    CONSTRAINT chk_gr_item_damaged CHECK (quantity_damaged >= 0),
    CONSTRAINT chk_gr_item_short CHECK (quantity_short >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(80) NOT NULL,
    vendor_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NULL,
    subtotal DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    tax_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    total_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    currency VARCHAR(3) NOT NULL DEFAULT 'PKR',
    status ENUM('draft', 'registered', 'verified', 'disputed', 'paid') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_supplier_invoices_number (invoice_number, vendor_id),
    KEY idx_supplier_invoices_vendor (vendor_id),
    KEY idx_supplier_invoices_order (purchase_order_id),
    KEY idx_supplier_invoices_status (status),
    KEY idx_supplier_invoices_due_date (due_date),
    CONSTRAINT fk_supplier_invoices_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_invoices_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_invoices_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_supplier_invoices_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_supplier_invoice_amounts CHECK (subtotal >= 0 AND tax_amount >= 0 AND total_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Purchasing finance posting placeholders (mirrors inventory pattern)
CREATE TABLE purchasing_finance_postings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_type ENUM('purchase_order', 'goods_receipt') NOT NULL,
    source_id BIGINT UNSIGNED NOT NULL,
    journal_entry_id BIGINT NULL,
    idempotency_key VARCHAR(100) NOT NULL,
    status ENUM('pending', 'posted', 'failed', 'not_required') NOT NULL DEFAULT 'pending',
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(1000) NULL,
    posted_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_purchasing_finance_source (source_type, source_id),
    UNIQUE KEY uk_purchasing_finance_idempotency (idempotency_key),
    CONSTRAINT fk_purchasing_finance_journal FOREIGN KEY (journal_entry_id) REFERENCES journal_entries (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert permissions
INSERT INTO permissions (name, description) VALUES
('purchasing.view', 'View purchase requests, orders, receipts, invoices, and procurement dashboards.'),
('purchasing.create', 'Create purchase requests, orders, and register supplier invoices.'),
('purchasing.update', 'Edit draft requests and orders; cancel requests and orders.'),
('purchasing.approve', 'Approve or reject purchase requests and purchase orders.'),
('purchasing.receive', 'Record goods receipts, partial receipts, and returns to supplier.'),
('purchasing.manage', 'Close purchase orders, manage procurement settings, and oversee finance postings.')
ON DUPLICATE KEY UPDATE description = VALUES(description);
