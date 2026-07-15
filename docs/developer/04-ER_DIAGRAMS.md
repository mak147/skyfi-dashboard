# Entity Relationship Diagrams

Mermaid ER diagrams for the **implemented** SkyFi schema. These diagrams highlight primary relationships used by application code; they are not exhaustive column catalogs. Full DDL lives in `backend/database/migrations/`.

## 1. Identity, RBAC, and auth

```mermaid
erDiagram
    users ||--o{ role_user : has
    roles ||--o{ role_user : grants
    roles ||--o{ permission_role : includes
    permissions ||--o{ permission_role : assigned
    users ||--o{ refresh_tokens : issues
    users ||--o{ password_resets : resets
    users ||--o| customers : "portal link (customer_id)"

    users {
        bigint id PK
        string email UK
        string password_hash
        bigint customer_id FK
        timestamp deleted_at
    }
    roles {
        bigint id PK
        string name UK
        string slug
    }
    permissions {
        bigint id PK
        string name UK
        string slug
    }
    refresh_tokens {
        bigint id PK
        bigint user_id FK
        string token_hash
        timestamp expires_at
    }
```

## 2. Customers, packages, and connections

```mermaid
erDiagram
    customers ||--o{ connections : owns
    packages ||--o{ connections : provisions
    package_categories ||--o{ packages : groups
    packages ||--o{ package_prices : priced_by
    packages ||--o| package_bandwidth_profiles : bandwidth
    packages ||--o| package_network_profiles : network
    packages ||--o| package_billing_settings : billing
    packages ||--o| package_technical_profiles : technical
    pop_sites ||--o{ towers : hosts
    towers ||--o{ sectors : radiates
    sectors ||--o{ connections : serves
    network_devices }o--|| pop_sites : located_at
    network_devices }o--o| towers : mounted_on

    customers {
        bigint id PK
        string customer_code UK
        string full_name
        string status
        timestamp deleted_at
    }
    packages {
        bigint id PK
        string name
        string status
    }
    connections {
        bigint id PK
        string connection_number UK
        bigint customer_id FK
        bigint package_id FK
        string type
        string status
    }
```

## 3. Billing and payments

```mermaid
erDiagram
    customers ||--o{ invoices : billed
    connections ||--o{ invoices : for_service
    packages ||--o{ invoices : priced_as
    invoices ||--|{ invoice_items : contains
    invoices ||--o{ invoice_activities : audited_by
    customers ||--o{ payments : pays
    payments ||--o{ payment_allocations : allocates
    invoices ||--o{ payment_allocations : receives
    payments ||--o{ receipts : documents
    payments ||--o{ payment_refunds : refunds
    payments ||--o{ payment_activities : history
    payment_methods ||--o{ payments : via
    payment_accounts ||--o{ payments : into
    customers ||--o{ customer_credit_ledger : credit

    invoices {
        bigint id PK
        string invoice_number UK
        bigint customer_id FK
        string status
        decimal total_amount
        decimal balance_due
    }
    payments {
        bigint id PK
        bigint customer_id FK
        decimal amount
        string status
    }
    payment_allocations {
        bigint id PK
        bigint payment_id FK
        bigint invoice_id FK
        decimal amount
    }
```

## 4. Finance ledger

```mermaid
erDiagram
    chart_of_accounts ||--o{ journal_entry_lines : classifies
    journal_entries ||--|{ journal_entry_lines : splits
    financial_accounts ||--o{ general_ledger : posts
    expenses }o--|| chart_of_accounts : expense_account
    revenue }o--|| chart_of_accounts : revenue_account

    chart_of_accounts {
        bigint id PK
        string code UK
        string name
        string type
    }
    journal_entries {
        bigint id PK
        string reference
        date entry_date
        string status
    }
    journal_entry_lines {
        bigint id PK
        bigint journal_entry_id FK
        bigint account_id FK
        decimal debit
        decimal credit
    }
```

## 5. Network operations (MikroTik / PPPoE / Hotspot)

```mermaid
erDiagram
    mikrotik_router_groups ||--o{ mikrotik_routers : groups
    mikrotik_routers ||--o{ mikrotik_router_tag_assignments : tagged
    mikrotik_router_tags ||--o{ mikrotik_router_tag_assignments : applied
    mikrotik_routers ||--o{ mikrotik_router_health_snapshots : health
    mikrotik_routers ||--o{ pppoe_accounts : hosts
    pppoe_accounts ||--o{ pppoe_session_history : sessions
    mikrotik_routers ||--o{ hotspot_users : hosts
    hotspot_profiles ||--o{ hotspot_users : profile
    hotspot_voucher_batches ||--o{ hotspot_vouchers : batch
    hotspot_users ||--o{ hotspot_session_history : sessions

    mikrotik_routers {
        bigint id PK
        string name
        string host
        string status
    }
    pppoe_accounts {
        bigint id PK
        string username UK
        bigint router_id FK
        string status
    }
    hotspot_users {
        bigint id PK
        string username UK
        bigint profile_id FK
        string status
    }
```

## 6. Support tickets

```mermaid
erDiagram
    customers ||--o{ support_tickets : opens
    support_teams ||--o{ support_team_members : members
    users ||--o{ support_team_members : staff
    ticket_categories ||--o{ support_tickets : categorizes
    sla_policies ||--o{ support_tickets : governs
    support_tickets ||--o{ ticket_comments : comments
    support_tickets ||--o{ ticket_assignments : assignments
    support_tickets ||--o{ ticket_history : history

    support_tickets {
        bigint id PK
        string ticket_number UK
        bigint customer_id FK
        string status
        string priority
    }
```

## 7. Inventory and purchasing

```mermaid
erDiagram
    inventory_categories ||--o{ inventory_products : classifies
    inventory_products ||--o{ inventory_stock_balances : stocked_as
    warehouses ||--o{ warehouse_locations : contains
    warehouses ||--o{ inventory_stock_balances : holds
    inventory_products ||--o{ inventory_assets : serializes
    inventory_stock_movements ||--|{ inventory_stock_movement_lines : lines
    inventory_warehouse_transfers ||--|{ inventory_warehouse_transfer_lines : lines
    vendors ||--o{ purchase_orders : supplies
    purchase_requests ||--o{ purchase_request_items : items
    purchase_orders ||--o{ purchase_order_items : items
    purchase_orders ||--o{ goods_receipts : receives
    goods_receipts ||--|{ goods_receipt_items : items
    purchase_orders ||--o{ supplier_invoices : billed

    inventory_products {
        bigint id PK
        string sku UK
        string name
    }
    warehouses {
        bigint id PK
        string code UK
        string name
    }
    purchase_orders {
        bigint id PK
        string po_number UK
        bigint vendor_id FK
        string status
    }
```

## 8. Field service

```mermaid
erDiagram
    field_teams ||--o{ field_team_members : members
    technicians ||--o{ field_team_members : belongs
    technicians ||--o{ technician_skills : skills
    technicians ||--o{ technician_availability : availability
    customers ||--o{ installation_requests : requests
    installation_requests ||--o{ work_orders : becomes
    technicians ||--o{ work_orders : assigned
    work_orders ||--o{ field_visits : visits
    work_orders ||--o{ work_logs : logs
    work_orders ||--o{ work_order_materials : materials
    work_orders ||--o{ work_order_history : history

    work_orders {
        bigint id PK
        string work_order_number UK
        string status
        bigint technician_id FK
    }
```

## 9. Notifications, audit, backup, integration, workflow

```mermaid
erDiagram
    notification_events ||--o{ notification_templates : templates
    notification_templates ||--o{ notifications : generates
    users ||--o{ notifications : receives
    notifications ||--o{ notification_deliveries : deliveries
    users ||--o{ audit_logs : actor
    workflows ||--o{ workflow_versions : versions
    workflows ||--o{ workflow_triggers : triggers
    workflows ||--o{ workflow_conditions : conditions
    workflows ||--o{ workflow_actions : actions
    workflows ||--o{ workflow_executions : executions
    client_applications ||--o{ api_keys : keys
    webhooks ||--o{ webhook_deliveries : deliveries
    backup_schedules ||--o{ backup_jobs : runs
    backup_jobs ||--o{ backup_files : produces

    workflows {
        bigint id PK
        string name
        string status
    }
    webhooks {
        bigint id PK
        string url
        string status
    }
    backup_jobs {
        bigint id PK
        string status
        timestamp started_at
    }
```

## 10. System administration

```mermaid
erDiagram
    companies ||--o{ branches : operates
    companies ||--o{ departments : organizes
    companies ||--o| branding_settings : brands
    companies ||--o| localization_settings : localizes
    companies ||--o| system_settings : configures
```

## 11. How to regenerate / extend diagrams

When schema changes:

1. Update the relevant migration SQL.
2. Adjust the Mermaid diagram(s) in this file in the same PR.
3. Keep diagrams relationship-focused; avoid listing every column.
4. For deep dives, link to the migration file name.

Rendering: GitHub and most Markdown previewers support Mermaid natively.
