Document 11: Entity Relationship Documentation
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document provides a detailed description of each major data entity within the SkyFi Networks system. It serves as a data dictionary and a guide to the relationships between entities. While Document 10 provided the high-level architecture and conventions, this document is the granular, field-level specification.

Its purpose is to be the single source of truth for the structure and meaning of the data, used by:

Backend Developers: To create models, repositories, and migrations.
Frontend Developers: To understand the data structures returned by the API and create corresponding TypeScript types.
QA Engineers: To write test cases with valid data and understand relationships for integration testing.
Product Owners: To understand the data that underpins the system's features.
2.0 General Conventions
Primary Key (PK): id (BIGINT UNSIGNED) is the standard primary key for all entities, unless otherwise specified.
Foreign Key (FK): Denoted by {table_name}_id. All FKs are BIGINT UNSIGNED and must have a corresponding index.
Timestamps: All tables include created_at and updated_at (TIMESTAMP).
Soft Deletes: Tables marked with (Soft Deletes) include a nullable deleted_at (TIMESTAMP) column.
Data Types: MySQL data types are specified.
Indexes: UK for Unique Index, IDX for a standard Index.
3.0 ERD (Entity Relationship Diagram) - Detailed View
This diagram expands on the one from Document 10, showing more entities and relationships critical to the core workflows.

mermaid

erDiagram
    customers {
        bigint id PK
        varchar(100) first_name
        varchar(100) last_name
        varchar(255) email UK
        varchar(20) phone
        enum('pending','active','suspended','inactive') status IDX
        timestamp deleted_at IDX
    }

    addresses {
        bigint id PK
        bigint customer_id FK
        enum('service','billing') type
        varchar(255) line1
        varchar(255) line2
        varchar(100) city
        varchar(100) state
        varchar(20) postal_code
        decimal(10,7) latitude
        decimal(10,7) longitude
    }

    services {
        bigint id PK
        bigint customer_id FK
        bigint service_plan_id FK
        bigint tower_id FK
        enum('pending','active','suspended','disconnected') status IDX
        varchar(64) pppoe_username UK
        varchar(64) pppoe_password
        varchar(45) static_ip
    }

    service_plans {
        int id PK
        varchar(100) name UK
        decimal(8,2) price
        enum('monthly','quarterly','yearly') billing_cycle
        int download_speed_mbps
        int upload_speed_mbps
    }
    
    invoices {
        bigint id PK
        bigint customer_id FK
        enum('draft','unpaid','paid','overdue','void') status IDX
        date issue_date
        date due_date IDX
        decimal(10,2) total_amount
        decimal(10,2) balance
        timestamp deleted_at IDX
    }

    invoice_items {
        bigint id PK
        bigint invoice_id FK
        varchar(255) description
        decimal(10,2) amount
        int quantity
    }

    payments {
        bigint id PK
        bigint invoice_id FK
        bigint customer_id FK
        decimal(10,2) amount
        enum('credit_card','bank_transfer','cash','credit') payment_method
        varchar(255) transaction_id IDX
        timestamp deleted_at IDX
    }

    users {
        int id PK
        varchar(100) name
        varchar(255) email UK
        varchar(255) password
        timestamp deleted_at IDX
    }

    roles {
        int id PK
        varchar(50) name UK
    }

    role_user {
        int user_id PK, FK
        int role_id PK, FK
    }

    towers {
        int id PK
        varchar(100) name UK
        decimal(10,7) latitude
        decimal(10,7) longitude
    }

    mikrotik_routers {
        int id PK
        int tower_id FK
        varchar(100) name
        varchar(45) ip_address
        varchar(100) api_username
        varchar(255) api_password_encrypted
    }

    customers ||--|{ addresses : "has"
    customers ||--o{ services : "subscribes to"
    customers ||--o{ invoices : "is billed with"
    customers ||--o{ payments : "makes"

    services }o--|| service_plans : "uses"
    services }o--|| towers : "is served by"

    invoices ||--|{ invoice_items : "details"
    invoices }o--|{ payments : "is settled by"
    
    users }o--|{ role_user : "has"
    roles ||--o{ role_user : "is assigned to"
    
    towers ||--o{ mikrotik_routers : "hosts"
4.0 Entity Definitions
4.1 customers (Soft Deletes)
Stores information about a subscriber. The central entity in the CRM.

Field	Type	Nullable	Description
id	BIGINT	No	PK: Unique Customer ID.
first_name	VARCHAR(100)	No	Customer's legal first name.
last_name	VARCHAR(100)	No	Customer's legal last name.
email	VARCHAR(255)	No	UK: Primary contact and login email.
phone	VARCHAR(20)	Yes	Primary contact phone number.
status	ENUM(...)	No	IDX: pending, active, suspended, inactive. Governs billing and service status.
deleted_at	TIMESTAMP	Yes	IDX: Timestamp for soft delete.
4.2 addresses
Stores physical addresses associated with a customer.

Field	Type	Nullable	Description
id	BIGINT	No	PK: Unique Address ID.
customer_id	BIGINT	No	FK: The customer this address belongs to.
type	ENUM(...)	No	service or billing. A customer can have multiple addresses.
line1, line2, city, ...	VARCHAR	...	Standard address fields.
latitude	DECIMAL(10,7)	Yes	GPS coordinate, essential for service availability checks.
longitude	DECIMAL(10,7)	Yes	GPS coordinate.
Relationship: A customer has many addresses. One for service and one for billing are typical.

4.3 service_plans
Defines the product catalog. These are the plans customers can subscribe to.

Field	Type	Nullable	Description
id	INT	No	PK: Unique Service Plan ID.
name	VARCHAR(100)	No	UK: Public name of the plan (e.g., "Home Basic 50/10").
price	DECIMAL(8,2)	No	The recurring price for the billing cycle.
billing_cycle	ENUM(...)	No	monthly, quarterly, yearly.
download_speed_mbps	INT	No	Download speed in Megabits per second. Used for provisioning.
upload_speed_mbps	INT	No	Upload speed in Megabits per second.
4.4 services
An instance of a service_plan subscribed to by a customer. The core NMS entity.

Field	Type	Nullable	Description
id	BIGINT	No	PK: Unique Service Instance ID.
customer_id	BIGINT	No	FK: The customer receiving this service.
service_plan_id	INT	No	FK: The plan this service is based on.
tower_id	INT	Yes	FK: The tower providing the signal.
status	ENUM(...)	No	IDX: pending, active, suspended, disconnected. Linked to customer and invoice status.
pppoe_username	VARCHAR(64)	Yes	UK: The username for the PPPoE session on the router.
pppoe_password	VARCHAR(64)	Yes	The password for the PPPoE session.
static_ip	VARCHAR(45)	Yes	The static IP address assigned to the service, if any.
Relationship: A customer can have many services. A service belongs to one service_plan.

4.5 invoices (Soft Deletes)
The core billing entity, representing a request for payment.

Field	Type	Nullable	Description
id	BIGINT	No	PK: Unique Invoice ID.
customer_id	BIGINT	No	FK: The customer being invoiced.
status	ENUM(...)	No	IDX: draft, unpaid, paid, overdue, void. Drives the dunning process.
issue_date	DATE	No	Date the invoice was generated.
due_date	DATE	No	IDX: Date payment is expected. Critical for overdue calculations.
total_amount	DECIMAL(10,2)	No	The sum of all invoice_items.
balance	DECIMAL(10,2)	No	total_amount minus payments. The current amount due.
4.6 invoice_items
A line item on an invoice.

Field	Type	Nullable	Description
id	BIGINT	No	PK: Unique Item ID.
invoice_id	BIGINT	No	FK: The invoice this line item belongs to.
description	VARCHAR(255)	No	Description of the charge (e.g., "Home Basic 50/10 (Jan 1 - Feb 1)").
amount	DECIMAL(10,2)	No	The total amount for this line (unit_price * quantity).
quantity	INT	No	The quantity of the item being charged for.
unit_price	DECIMAL(10,2)	No	The price per unit.
Relationship: An invoice has many invoice_items.

4.7 payments (Soft Deletes)
Records a financial transaction, typically applied against an invoice.

Field	Type	Nullable	Description
id	BIGINT	No	PK: Unique Payment ID.
invoice_id	BIGINT	Yes	FK: The invoice this payment is for. Can be NULL for on-account credits.
customer_id	BIGINT	No	FK: The customer who made the payment.
amount	DECIMAL(10,2)	No	The amount of money paid.
payment_method	ENUM(...)	No	credit_card, bank_transfer, cash, credit (from customer balance).
transaction_id	VARCHAR(255)	Yes	IDX: The unique ID from the payment gateway (e.g., Stripe's ch_... ID).
4.8 users, roles, role_user (Users has Soft Deletes)
Standard RBAC implementation.

users: Stores staff accounts for logging into the system. password must be a secure hash.
roles: Defines the roles available (e.g., 'Super Administrator', 'Support Agent').
role_user: A pivot table linking users to roles (many-to-many).
Relationship: A user can have many roles. A role can be assigned to many users.

4.9 towers & mikrotik_routers
Core entities for physical network infrastructure management.

towers: Represents a physical tower or point-of-presence. Primarily identified by name and GPS coordinates.
mikrotik_routers: Represents a specific MikroTik device. It belongs to a tower and holds the connection credentials. The api_password must be encrypted in the database, not stored as plaintext.
5.0 Data Integrity Rules (Enforced by Constraints)
A service cannot exist without a valid customer and service_plan.
An invoice cannot exist without a valid customer.
A payment cannot be recorded against a non-existent invoice.
A service's pppoe_username must be unique across the entire system to avoid conflicts on the routers.
A customer's email must be unique. A user's email must be unique.
ON DELETE behavior:
If a customer is deleted (soft delete), it should not cascade. Historical invoices/payments must remain.
If an invoice is deleted (soft delete), its invoice_items should remain for auditing.
If a service_plan is deleted, the ON DELETE rule for services.service_plan_id must be RESTRICT, preventing deletion if any active services are using it. An admin must first migrate customers to a new plan.
6.0 Future Expansion Considerations
Multi-Tenancy/Regionalization: Many tables (customers, towers, invoices) will eventually need a region_id FK to support data segmentation for regional managers. This should be added early in the design.
Inventory: Entities like inventory_items, warehouses, and purchase_orders will be added, linking to vendors and service_plans (for bundled equipment).
Other Vendors: To support Ubiquiti, a ubiquiti_devices table might be added, or the mikrotik_routers table could be generalized to a network_devices table with a vendor type column and a JSON column for vendor-specific attributes.
