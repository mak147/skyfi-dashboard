-- Payments Management module schema. Monetary mutations are append-oriented and auditable.
CREATE TABLE payment_methods (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, code VARCHAR(50) NOT NULL, name VARCHAR(100) NOT NULL,
 category ENUM('cash','bank','wallet','card','gateway') NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1,
 is_future TINYINT(1) NOT NULL DEFAULT 0, requires_reference TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uk_payment_methods_code(code), KEY idx_payment_methods_active(is_active,is_future)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_accounts (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payment_method_id BIGINT UNSIGNED NULL, code VARCHAR(50) NOT NULL,
 name VARCHAR(120) NOT NULL, account_type ENUM('cash','bank','wallet','gateway_clearing') NOT NULL,
 currency CHAR(3) NOT NULL DEFAULT 'PKR', is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL, updated_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL,
 PRIMARY KEY(id), UNIQUE KEY uk_payment_accounts_code(code), KEY idx_payment_accounts_method_active(payment_method_id,is_active),
 CONSTRAINT fk_payment_accounts_method FOREIGN KEY(payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payment_accounts_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_payment_accounts_updated_by FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payment_number VARCHAR(50) NOT NULL, customer_id BIGINT UNSIGNED NOT NULL,
 connection_id BIGINT UNSIGNED NULL, payment_method_id BIGINT UNSIGNED NOT NULL, payment_account_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(12,2) NOT NULL, tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0, discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 adjustment_amount DECIMAL(12,2) NOT NULL DEFAULT 0, applied_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 refunded_amount DECIMAL(12,2) NOT NULL DEFAULT 0, currency CHAR(3) NOT NULL DEFAULT 'PKR', payment_date DATETIME NOT NULL,
 reference_number VARCHAR(150) NULL, collected_by BIGINT UNSIGNED NOT NULL,
 status ENUM('pending','completed','partially_applied','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
 notes TEXT NULL, created_by BIGINT UNSIGNED NOT NULL, updated_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 deleted_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY uk_payments_number(payment_number),
 KEY idx_payments_customer_date(customer_id,payment_date), KEY idx_payments_status_date(status,payment_date),
 KEY idx_payments_reference(reference_number), KEY idx_payments_connection(connection_id), KEY idx_payments_method(payment_method_id),
 KEY idx_payments_account(payment_account_id), KEY idx_payments_collector(collected_by), KEY idx_payments_deleted(deleted_at),
 CONSTRAINT fk_payments_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payments_connection FOREIGN KEY(connection_id) REFERENCES connections(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payments_method FOREIGN KEY(payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payments_account FOREIGN KEY(payment_account_id) REFERENCES payment_accounts(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payments_collector FOREIGN KEY(collected_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payments_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payments_updated_by FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT chk_payments_amount CHECK(amount > 0), CONSTRAINT chk_payments_components CHECK(tax_amount >= 0 AND discount_amount >= 0 AND refunded_amount >= 0 AND applied_amount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_allocations (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payment_id BIGINT UNSIGNED NOT NULL, invoice_id BIGINT UNSIGNED NULL,
 allocation_type ENUM('invoice','advance_balance','credit_balance') NOT NULL DEFAULT 'invoice', amount DECIMAL(12,2) NOT NULL,
 allocated_at DATETIME NOT NULL, allocated_by BIGINT UNSIGNED NOT NULL, reversed_at DATETIME NULL, reversed_by BIGINT UNSIGNED NULL,
 notes VARCHAR(500) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_allocations_payment_active(payment_id,reversed_at), KEY idx_allocations_invoice_active(invoice_id,reversed_at),
 CONSTRAINT fk_allocations_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
 CONSTRAINT fk_allocations_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
 CONSTRAINT fk_allocations_by FOREIGN KEY(allocated_by) REFERENCES users(id) ON DELETE RESTRICT,
 CONSTRAINT fk_allocations_reversed_by FOREIGN KEY(reversed_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT chk_allocations_amount CHECK(amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE receipts (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payment_id BIGINT UNSIGNED NOT NULL, receipt_number VARCHAR(50) NOT NULL,
 issued_at DATETIME NOT NULL, issued_by BIGINT UNSIGNED NOT NULL, snapshot JSON NOT NULL,
 pdf_status ENUM('placeholder','ready','failed') NOT NULL DEFAULT 'placeholder', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL,
 PRIMARY KEY(id), UNIQUE KEY uk_receipts_payment(payment_id), UNIQUE KEY uk_receipts_number(receipt_number), KEY idx_receipts_issued(issued_at),
 CONSTRAINT fk_receipts_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
 CONSTRAINT fk_receipts_issued_by FOREIGN KEY(issued_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_refunds (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, refund_number VARCHAR(50) NOT NULL, payment_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(12,2) NOT NULL, status ENUM('pending','completed','failed','cancelled') NOT NULL DEFAULT 'completed',
 reason VARCHAR(255) NOT NULL, notes TEXT NULL, reference_number VARCHAR(150) NULL, refunded_at DATETIME NOT NULL,
 refunded_by BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at TIMESTAMP NULL,
 PRIMARY KEY(id), UNIQUE KEY uk_refunds_number(refund_number), KEY idx_refunds_payment_status(payment_id,status),
 CONSTRAINT fk_refunds_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
 CONSTRAINT fk_refunds_by FOREIGN KEY(refunded_by) REFERENCES users(id) ON DELETE RESTRICT, CONSTRAINT chk_refund_amount CHECK(amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_activities (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payment_id BIGINT UNSIGNED NOT NULL, action VARCHAR(60) NOT NULL,
 description TEXT NULL, metadata JSON NULL, performed_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_payment_activities_payment(payment_id,created_at),
 CONSTRAINT fk_payment_activities_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payment_activities_user FOREIGN KEY(performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_credit_ledger (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, customer_id BIGINT UNSIGNED NOT NULL, payment_id BIGINT UNSIGNED NULL,
 allocation_id BIGINT UNSIGNED NULL, entry_type ENUM('advance','credit','applied','reversal','refund') NOT NULL,
 amount DECIMAL(12,2) NOT NULL, description VARCHAR(255) NULL, effective_at DATETIME NOT NULL, created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), KEY idx_credit_customer_date(customer_id,effective_at),
 CONSTRAINT fk_credit_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
 CONSTRAINT fk_credit_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
 CONSTRAINT fk_credit_allocation FOREIGN KEY(allocation_id) REFERENCES payment_allocations(id) ON DELETE RESTRICT,
 CONSTRAINT fk_credit_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_attachments (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payment_id BIGINT UNSIGNED NOT NULL, original_name VARCHAR(255) NOT NULL,
 mime_type VARCHAR(100) NULL, storage_key VARCHAR(500) NULL, uploaded_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id), KEY idx_payment_attachments_payment(payment_id),
 CONSTRAINT fk_payment_attachments_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
 CONSTRAINT fk_payment_attachments_user FOREIGN KEY(uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_financial_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, payment_id BIGINT UNSIGNED NOT NULL, refund_id BIGINT UNSIGNED NULL,
 event_type VARCHAR(60) NOT NULL, amount DECIMAL(12,2) NOT NULL, currency CHAR(3) NOT NULL DEFAULT 'PKR', payload JSON NOT NULL,
 idempotency_key VARCHAR(150) NOT NULL, processing_status ENUM('pending','processed','failed') NOT NULL DEFAULT 'pending',
 occurred_at DATETIME NOT NULL, processed_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uk_financial_events_idempotency(idempotency_key), KEY idx_financial_events_status(processing_status,occurred_at),
 CONSTRAINT fk_financial_events_payment FOREIGN KEY(payment_id) REFERENCES payments(id) ON DELETE RESTRICT,
 CONSTRAINT fk_financial_events_refund FOREIGN KEY(refund_id) REFERENCES payment_refunds(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_methods(code,name,category,is_active,is_future,requires_reference) VALUES
('cash','Cash','cash',1,0,0),('bank_transfer','Bank Transfer','bank',1,0,1),('jazzcash','JazzCash','wallet',1,0,1),
('easypaisa','Easypaisa','wallet',1,0,1),('credit_card','Credit Card','card',0,1,1),('debit_card','Debit Card','card',0,1,1),
('online_gateway','Online Gateway','gateway',0,1,1);
INSERT INTO payment_accounts(payment_method_id,code,name,account_type) SELECT id,'CASH-ON-HAND','Cash on Hand','cash' FROM payment_methods WHERE code='cash';
INSERT INTO payment_accounts(payment_method_id,code,name,account_type) SELECT id,'BANK-CLEARING','Bank Clearing','bank' FROM payment_methods WHERE code='bank_transfer';
INSERT INTO payment_accounts(payment_method_id,code,name,account_type) SELECT id,'JAZZCASH-WALLET','JazzCash Wallet','wallet' FROM payment_methods WHERE code='jazzcash';
INSERT INTO payment_accounts(payment_method_id,code,name,account_type) SELECT id,'EASYPAISA-WALLET','Easypaisa Wallet','wallet' FROM payment_methods WHERE code='easypaisa';

INSERT INTO permissions(name,description) VALUES
('payments.view','View payments, receipts, allocations, and statistics.'),('payments.create','Create and receive payments.'),
('payments.update','Update eligible pending payments.'),('payments.delete','Soft-delete eligible payment records.'),
('payments.refund','Issue full and partial payment refunds.'),('payments.export','Export payment data and receipts.'),
('payments.manage','Allocate, reverse, and bulk-manage payments.');
