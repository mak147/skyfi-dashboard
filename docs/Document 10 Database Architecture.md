Document 10: Database Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document describes the architecture of the MySQL database for the SkyFi Networks ISP Management System. It specifies the design philosophy, schema conventions, integrity rules, and performance considerations. The database is the system's core, and its architecture is paramount for ensuring data integrity, performance, and scalability.

This document serves as the master guide for developers and database administrators for creating, modifying, and interacting with the database schema.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define and own the database architecture and design principles.
Development Lead	Enforce schema conventions and best practices during code reviews. Review all database migrations.
Developers	Design tables and write queries in accordance with this document. Create migration files for all schema changes.
Database Administrator (DBA)	Monitor database performance, manage backups, and optimize queries based on production load. (Note: In a cloud environment, this role often overlaps with DevOps).
3.0 Goals
Data Integrity: Ensure the accuracy, consistency, and reliability of data through the strict use of data types, constraints, and relationships. This is the highest priority.
Performance: Design a schema that supports fast and efficient data retrieval for both transactional (OLTP) and reporting workloads.
Scalability: Create a database structure that can grow to support millions of records across key tables without significant performance degradation, addressing NFR-SCAL-004.
Maintainability: Use clear and consistent naming conventions to make the schema easy to understand and evolve over time.
Traceability: Ensure every change to the database schema is version-controlled and auditable.
4.0 Database Technology: MySQL 8.x
Engine: InnoDB will be used for all tables.
Character Set: utf8mb4 with utf8mb4_unicode_ci collation.
Architectural Justification:
MySQL: A mature, reliable, well-understood, and widely supported open-source RDBMS. The chosen PHP technology stack has excellent support for it.
InnoDB: The default and standard engine. Its support for transactions (ACID compliance) and row-level locking is essential for a system with concurrent financial and provisioning operations. Foreign key constraints are also critical for data integrity.
utf8mb4: This character set is required to support the full range of Unicode characters, including emojis, which may appear in customer names, notes, or support tickets.
5.0 Schema Design Principles
Third Normal Form (3NF): The schema will be designed to adhere to 3NF as a baseline. This reduces data redundancy and improves data integrity by ensuring that all table attributes are dependent only on the primary key. Denormalization will be a conscious, documented decision made only for specific, measurable performance reasons (e.g., in a dedicated reporting table).
Relational Integrity: Foreign key constraints must be used to enforce relationships between tables. The ON DELETE and ON UPDATE behaviors will be explicitly defined (typically RESTRICT or SET NULL to prevent accidental data loss).
Data Types: Use the most specific and smallest appropriate data type for each column to save space and improve performance (e.g., TINYINT(1) for booleans, DECIMAL for currency, INT vs BIGINT for IDs).
Soft Deletes: Critical records that have legal or financial significance must not be physically deleted from the database. A deleted_at (TIMESTAMP, nullable) column will be used. This "soft delete" approach is essential for auditing, data recovery, and maintaining historical accuracy. This applies to tables like customers, invoices, payments, users.
Timestamps: All tables must include created_at and updated_at TIMESTAMP columns, managed automatically by the database or ORM. This provides a crucial audit trail for every record.
6.0 Naming Conventions
Consistency is key to a maintainable schema.

Element	Convention	Example
Tables	Plural, snake_case	customers, service_plans, invoice_items
Columns	Singular, snake_case	first_name, street_address, due_date
Primary Keys	id	id (Unsigned BIGINT, Auto Increment)
Foreign Keys	{singular_table_name}_id	customer_id, service_plan_id
Boolean Columns	is_{property} or has_{property}	is_active, has_late_fee_applied
Timestamp Columns	{action}_at	created_at, updated_at, deleted_at, suspended_at
Indexes	idx_{table_name}_{columns}	idx_invoices_customer_id_status
Foreign Key Constraints	fk_{table_name}_{foreign_table_name}	fk_invoices_customers
7.0 Core Schema Diagram (High-Level ERD)
This diagram shows the primary entities and their most important relationships. It is not exhaustive but illustrates the core structure.

mermaid

erDiagram
    customers {
        bigint id PK
        varchar first_name
        varchar last_name
        varchar email UK
        enum status
        timestamp deleted_at
    }

    services {
        bigint id PK
        bigint customer_id FK
        bigint service_plan_id FK
        enum status
        varchar pppoe_username
    }

    service_plans {
        int id PK
        varchar name
        decimal price
        varchar billing_cycle
    }

    invoices {
        bigint id PK
        bigint customer_id FK
        enum status
        date due_date
        decimal total_amount
        timestamp deleted_at
    }

    invoice_items {
        bigint id PK
        bigint invoice_id FK
        varchar description
        decimal amount
    }

    payments {
        bigint id PK
        bigint invoice_id FK
        bigint customer_id FK
        decimal amount
        varchar payment_method
        varchar transaction_id
        timestamp deleted_at
    }

    users {
        int id PK
        varchar name
        varchar email UK
        varchar password
    }

    support_tickets {
        bigint id PK
        bigint customer_id FK
        bigint assigned_to_id FK "user id"
        varchar subject
        enum status
    }
    
    customers ||--|{ services : "has"
    customers ||--|{ invoices : "receives"
    customers ||--|{ payments : "makes"
    customers ||--|{ support_tickets : "opens"
    
    services }o--|| service_plans : "is of type"
    
    invoices ||--|{ invoice_items : "contains"
    invoices ||--|{ payments : "is paid by"

    support_tickets }o--|| users : "is assigned to"
8.0 Key Table Definitions (Examples)
Table: customers

Column Name	Data Type	Modifiers	Description
id	BIGINT	UNSIGNED, NOT NULL, AUTO_INCREMENT, PK	Unique identifier for the customer.
first_name	VARCHAR(100)	NOT NULL	Customer's first name.
last_name	VARCHAR(100)	NOT NULL	Customer's last name.
email	VARCHAR(255)	NOT NULL, UNIQUE	Customer's primary email address. Used for login and notifications.
status	ENUM(...)	NOT NULL, DEFAULT 'pending'	Current status of the customer (pending, active, suspended, inactive).
created_at	TIMESTAMP	NOT NULL, DEFAULT CURRENT_TIMESTAMP	Record creation timestamp.
updated_at	TIMESTAMP	NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP	Record last update timestamp.
deleted_at	TIMESTAMP	NULL	Timestamp for soft deletes. If NULL, record is active.
Table: invoices

Column Name	Data Type	Modifiers	Description
id	BIGINT	UNSIGNED, NOT NULL, AUTO_INCREMENT, PK	Unique identifier for the invoice.
customer_id	BIGINT	UNSIGNED, NOT NULL, FK to customers.id	The customer this invoice belongs to.
status	ENUM(...)	NOT NULL, DEFAULT 'draft'	Current status of the invoice (draft, unpaid, paid, overdue, void).
issue_date	DATE	NOT NULL	The date the invoice was generated.
due_date	DATE	NOT NULL	The date the invoice payment is due.
total_amount	DECIMAL(10,2)	NOT NULL	The total amount of the invoice.
balance	DECIMAL(10,2)	NOT NULL	The remaining amount due on the invoice.
...	...		created_at, updated_at, deleted_at.
Architectural Decision: Using DECIMAL(10, 2) for all currency values is non-negotiable. Using FLOAT or DOUBLE can lead to rounding errors, which is unacceptable in a financial system.

9.0 Indexing Strategy
Indexes are critical for performance but have a write-time overhead. They must be added judiciously.

Primary Keys: All tables will have an auto-incrementing id as the primary key.
Foreign Keys: Indexes will be created automatically on all foreign key columns by InnoDB. This is essential for JOIN performance.
Common Lookups: Manual indexes will be added to columns frequently used in WHERE clauses, JOINs, or ORDER BY operations.
customers(email): For login and lookups.
invoices(status, due_date): For the dunning process to find overdue invoices efficiently.
services(pppoe_username): For the NMS to quickly find a service based on data from a router.
Composite Indexes: Multi-column indexes will be used for queries that filter on multiple attributes. The column with the highest cardinality should be placed first.
idx_invoices_customer_id_status: For fetching all invoices of a specific status for a given customer.
10.0 Database Migrations
Version Control for Schema: All changes to the database schema (creating/modifying/dropping tables or columns) must be done through a migration system (e.g., the one built into Laravel, or a standalone tool like Phinx).
Workflow:
A developer creates a new migration file.
The file is committed to the Git repository.
During code review, the migration file is reviewed along with the application code.
The CI/CD pipeline automatically runs the migrations on the staging/production database during deployment.
No Manual Changes: Direct manual changes to the production database schema (ALTER TABLE ...) are strictly forbidden. This ensures that all environments are consistent and all changes are reproducible and auditable.
11.0 Performance and Scalability
Read Replicas: The architecture (as defined in Doc 06) supports using an RDS Read Replica. The application must be designed to direct read-heavy, non-critical queries (e.g., for generating analytics reports) to the read replica to reduce load on the primary master database.
Query Optimization: The development team is responsible for writing efficient queries. A policy will be in place to use EXPLAIN on all complex queries to analyze their execution plan and ensure they are using appropriate indexes. A maximum query time threshold will be monitored.
Connection Pooling: The application will use persistent database connections and connection pooling to reduce the latency and overhead of establishing a new connection for every request.
12.0 Risks
Risk	Description	Mitigation Strategy
Schema "Sprawl"	The schema becomes chaotic and inconsistent over time due to a lack of discipline.	Strict enforcement of naming conventions and design principles through mandatory code reviews of all migrations.
"N+1" Query Problems	Inefficient data retrieval patterns in the ORM (e.g., loading a list of 100 invoices and then running a separate query for each invoice's customer) can cripple performance.	Eager loading must be used as the default pattern for loading related data. Automated tools (e.g., Laravel Telescope) will be used to detect N+1 queries during development.
Migration Conflicts	Multiple developers create migrations that conflict with each other.	Good communication and a rebase-heavy Git workflow (git pull --rebase). Migrations are timestamped, which helps, but developers should pull the latest changes before creating a new migration.
Large Table Performance	Tables like invoice_items or audit_logs could grow to hundreds of millions of rows, slowing down queries and maintenance.	Implement a data archiving or partitioning strategy for historical data. For example, move invoice data older than 7 years to a separate set of "archive" tables or a data warehouse. This must be planned for in advance (NFR-SCAL-004).