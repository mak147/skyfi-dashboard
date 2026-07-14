Document 15: User Roles
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document provides a detailed specification of the user roles within the SkyFi Networks ISP Management System. It defines each role's purpose and explicitly maps it to the granular permissions it possesses. This serves as the initial configuration for the Role-Based Access Control (RBAC) system.

The purpose of this document is to:

Provide clarity to business stakeholders on what each type of user can do.
Guide administrators in assigning the correct roles to users.
Serve as a definitive requirements list for developers implementing the RBAC seeders and policies.
Provide a basis for the QA team to create role-specific test plans.
2.0 Responsibilities
Role	Responsibility
Product Owner & Dept. Heads	Review and approve this list, ensuring it accurately reflects operational needs and job functions.
Principal Architect	Define the structure and ensure the roles align with the RBAC architecture.
Backend Developers	Implement the initial database seeder to create these roles and their permission mappings.
Super Administrator	Use this document as a guide for day-to-day user management.
3.0 General Principles
Principle of Least Privilege: Each role is designed to have only the permissions strictly necessary for its defined function.
Separation of Duties: Critical financial tasks are separated. For example, a role might be able to create an invoice but not approve a large refund.
Composability: Users can be assigned multiple roles. For example, a senior support agent might have both the Customer Support and Finance Clerk roles.
Scoped Permissions: Permissions marked with (S) are intended to be scoped by data-level policies (e.g., a Regional Manager can only view:customer for customers in their assigned region). The base permission is granted here, but the policy enforces the scope.
4.0 Permission Legend
Action	Description
view	Read/view data.
create	Create a new record.
update	Modify an existing record.
delete	Soft-delete a record.
execute	Trigger a process or action.
manage	A shortcut for view, create, update, delete.
5.0 Role and Permission Matrix
5.1 Super Administrator
Purpose: Has complete, unrestricted access to the entire system. Responsible for system configuration, user management, and disaster recovery. This role is highly privileged and should be assigned to a minimal number of trusted individuals.
Permissions: * (Wildcard for all permissions)
5.2 Company Owner
Purpose: Provides high-level, read-only visibility into the health of the business. Cannot make operational changes.
Permissions:
view:dashboard:company
view:report:financial
view:report:subscriber
view:report:network
view:audit-log
5.3 Regional Manager
Purpose: Manages all operational aspects of a specific geographical region.
Permissions:
view:dashboard:regional
view:customer (S)
view:invoice (S)
view:payment (S)
view:ticket (S)
view:tower (S)
view:report:regional
update:user:role (S - Can only manage roles of users within their region)
view:work-order (S)
5.4 Finance Department
Purpose: Manages the entire quote-to-cash lifecycle. The most powerful role for day-to-day financial operations.
Permissions:
manage:invoice
manage:payment
manage:credit
create:refund:small (e.g., up to $100)
create:refund:large (e.g., requires additional approval or this specific permission)
execute:billing-run
execute:dunning-process
manage:service-plan
view:customer
view:report:financial
5.5 Sales Team
Purpose: Focuses on lead generation and new customer acquisition.
Permissions:
manage:lead
execute:service-availability-check
create:quote
create:customer
view:customer:basic (S - Can only view customers they created until activated)
view:service-plan
5.6 Customer Support
Purpose: The primary point of contact for customer issues. Needs broad read-access to diagnose problems but limited write-access.
Permissions:
manage:ticket
view:customer
update:customer:contact (Can update phone/email, but not change status)
update:customer:notes
view:invoice
view:payment
create:payment:manual (For taking payments over the phone)
view:service
view:network-status:customer (Can see a customer's signal strength, latency, etc.)
execute:service:reconnect (Can re-send the PPPoE enable command if a customer paid but service didn't restore)
5.7 Installation Team / Field Technician
Purpose: Manages the physical installation and repair of services. Access should be streamlined for mobile/field use.
Permissions:
view:work-order:own (S - Can only see work orders assigned to them)
update:work-order:own (S - Can update status, add notes, upload photos)
view:customer:contact_and_address (S - Only for customers on their assigned work orders)
view:inventory:own-vehicle (S - Can see stock levels in their own vehicle)
execute:site-survey
execute:service:diagnostics (Can run pings, etc., from the system)
5.8 Network Engineer
Purpose: Manages the core network infrastructure, including routers and towers. Has powerful tools to affect network-wide services.
Permissions:
manage:tower
manage:mikrotik-router
view:ip-address-pool
execute:provisioning:manual (Can manually create/disable a PPPoE user)
execute:config-backup
view:network-status:global
view:customer:network-details (Can see any customer's network service info)
view:report:network
5.9 Inventory Manager
Purpose: Manages all physical assets, from purchasing to stock levels in warehouses and vehicles.
Permissions:
manage:inventory-item
manage:warehouse
execute:stock-transfer
manage:vendor
manage:purchase-order
view:report:inventory
5.10 Customer (Implicit Role)
Purpose: This is not a formal, assignable role in the admin panel. It is an implicit role enforced by data-level policies for users logging into the customer portal. All permissions are scoped to the user's own data.
Permissions:
view:customer:own (S)
update:customer:own (S)
view:invoice:own (S)
create:payment:own (S)
view:service:own (S)
manage:ticket:own (S)
6.0 Visualization of Role Overlap
mermaid

graph TD
    subgraph "High Privilege"
        A[Super Administrator]
    end

    subgraph "Management"
        B[Company Owner]
        C[Regional Manager]
    end
    
    subgraph "Operations"
        D[Finance Dept]
        E[Sales Team]
        F[Customer Support]
        G[Installation Team]
        H[Network Engineer]
        I[Inventory Manager]
    end

    subgraph "External"
        J[Customer]
    end
    
    A --> B; A --> C; A --> D
    C --> F; C --> G

    D <--> F
    F <--> G
    G <--> H
    I <--> G
    E --> F
Diagram Explanation: This informal diagram shows common interaction pathways. For instance, the Finance Dept and Customer Support roles overlap significantly in their need to view customer and billing data. Installation and Network Engineer roles both interact with the network but at different levels.

7.0 Implementation Notes
Database Seeding: A database seeder script must be created to populate the permissions and roles tables, and to create the permission_role mappings on a fresh installation. This ensures a consistent baseline across all environments.
Management UI: A dedicated section in the Super Administrator's UI must be created to manage this matrix. It should allow an admin to:
Create and edit roles.
View a list of all available permissions (read-only, as these are defined in code).
Assign/unassign permissions to roles using a checklist interface.
Assign/unassign roles to users.
Extensibility: When a developer creates a new feature that requires a new permission, their workflow must be:
Add the new permission string to a centralized list (e.g., an Enum or a config file).
Update the seeder to include the new permission in the permissions table.
Secure the new feature's endpoint/service method with a check for this permission.
Notify the Product Owner/Admin that a new permission is available to be assigned to roles.
8.0 Risks
Risk	Description	Mitigation Strategy
Role Misconfiguration	An administrator accidentally grants excessive permissions to a role.	The role management UI should have a clear, descriptive interface. A "review changes" step before saving could be implemented. The Super Administrator role should be protected by MFA.
Stale Documentation	The roles and permissions change in the application, but this document is not updated.	This document represents the initial state. The running application's admin panel becomes the source of truth for the current role-permission mapping. The list of available permissions, however, should always be kept in sync with the codebase.