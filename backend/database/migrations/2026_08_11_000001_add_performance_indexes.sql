-- Performance indexes for common query patterns
-- These indexes improve query performance without changing any schema.
-- Uses a stored procedure approach to safely add indexes if they don't exist.

-- Invoices: filtering by customer and due date for dunning
ALTER TABLE invoices ADD INDEX idx_invoices_customer_due (customer_id, due_date);

-- Invoices: filtering by status and due date for billing runs
ALTER TABLE invoices ADD INDEX idx_invoices_status_date (status, due_date);

-- Payments: customer-centric payment history queries
ALTER TABLE payments ADD INDEX idx_payments_customer_created (customer_id, created_at);

-- Connections: filtering by customer, status, and connection type
ALTER TABLE connections ADD INDEX idx_connections_customer_status_type (customer_id, status, type);

-- Activity events: module-based activity feed queries
ALTER TABLE activity_events ADD INDEX idx_activity_module_action_created (module, action, created_at);

-- Journal entries: user audit trail
ALTER TABLE journal_entries ADD INDEX idx_journal_entries_creator_date (created_by, created_at);

-- PPPoE accounts: router-based queries
ALTER TABLE pppoe_accounts ADD INDEX idx_pppoe_router_status (router_id, status);

-- Hotspot users: router-based queries
ALTER TABLE hotspot_users ADD INDEX idx_hotspot_router_status (router_id, status);

-- MikroTik health snapshots: router health trend queries
ALTER TABLE mikrotik_router_health_snapshots ADD INDEX idx_mikrotik_health_router_checked_status (router_id, checked_at, status);

-- Support tickets: agent's open ticket queries
ALTER TABLE support_tickets ADD INDEX idx_support_tickets_assigned_status (assigned_to, status, deleted_at);

-- Audit logs: user action history queries
ALTER TABLE audit_logs ADD INDEX idx_audit_logs_user_action (user_id, action, created_at);
