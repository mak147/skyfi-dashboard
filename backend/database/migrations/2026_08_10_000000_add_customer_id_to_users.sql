-- Customer Self-Service Portal: link portal users to customer records.
-- Apply through the project's migration runner; do not alter production manually.

ALTER TABLE users
    ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER email,
    ADD KEY idx_users_customer_id (customer_id),
    ADD CONSTRAINT fk_users_customer_id
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON DELETE SET NULL ON UPDATE CASCADE;
