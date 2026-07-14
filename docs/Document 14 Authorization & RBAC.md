Document 14: Authorization & RBAC
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the design for the Authorization and Role-Based Access Control (RBAC) system for the SkyFi Networks platform. While authentication confirms a user's identity, authorization determines what actions that authenticated user is permitted to perform.

The purpose is to create a granular, flexible, and centrally managed system to enforce security policies, protect sensitive data, and ensure users can only access the features and data relevant to their job function. This directly addresses the NFR-SEC-003 and FR-SYS-003 requirements.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define the RBAC model, including the structure of roles, permissions, and the enforcement mechanism.
Backend Developers	Implement the RBAC middleware and logic. Secure all API endpoints and service methods with appropriate permission checks.
Frontend Developers	Implement UI controls that conditionally render or disable elements (buttons, links, menu items) based on the current user's permissions.
Product Owner/Dept. Heads	Define the specific permissions required for each business role (e.g., "What can a Support Agent do?").
Super Administrator	Manage roles and permissions through the application's admin interface once built.
3.0 Goals
Enforce Least Privilege: Users must be granted only the minimum permissions necessary to perform their duties.
Centralized Management: Provide a user-friendly interface for administrators to manage roles and assign permissions without requiring code changes.
Granularity: Allow for the definition of fine-grained permissions for specific actions (e.g., create:invoice vs. delete:invoice).
Flexibility: The system must support users having multiple roles and be easily extensible with new permissions as the application grows.
Auditability: All permission checks and changes to roles/permissions must be auditable.
4.0 RBAC Model: Permissions, Roles, and Users
Our RBAC system will be based on three core concepts:

Permissions: A Permission is a single, atomic action that can be performed in the system. It is a string that represents a specific capability.
Roles: A Role is a named collection of Permissions. Roles represent job functions within the company (e.g., "Finance Department", "Field Technician").
Users: A User is an individual who is assigned one or more Roles. The user inherits all the permissions from all the roles they are assigned.
4.1 Entity Relationship Diagram

mermaid

erDiagram
    users {
        int id PK
        varchar name
        varchar email
    }

    roles {
        int id PK
        varchar name UK
        varchar description
    }

    permissions {
        int id PK
        varchar name UK
        varchar description
    }

    role_user {
        int user_id PK, FK
        int role_id PK, FK
    }

    permission_role {
        int permission_id PK, FK
        int role_id PK, FK
    }

    users }o--|{ role_user : "is assigned"
    roles ||--o{ role_user : "contains"
    
    roles }o--|{ permission_role : "is granted"
    permissions ||--o{ permission_role : "is part of"
4.2 Database Schema

permissions table:
id (INT, PK), name (VARCHAR, UK), description (VARCHAR).
This table will be seeded by the application with all possible permissions. It is managed by developers, not administrators.
roles table:
id (INT, PK), name (VARCHAR, UK), description (VARCHAR).
Managed by Super Administrators via the UI.
permission_role (pivot table):
permission_id (FK), role_id (FK). Many-to-many relationship.
role_user (pivot table):
user_id (FK), role_id (FK). Many-to-many relationship.
5.0 Permission Naming Convention
A consistent naming convention for permissions is critical for clarity.

Format: {action}:{resource} or {action}:{resource}:{scope}
Actions: view, create, update, delete, execute
Examples:
view:customer - Permission to view customer details.
create:invoice - Permission to create a new invoice.
delete:payment - Permission to delete a payment record.
execute:dunning - Permission to manually trigger the dunning process.
view:report:financial - Permission to view reports in the "financial" category.
A special * wildcard can be used for god-mode roles.

* - Grants all permissions (Super Administrator only).
view:* - Grants permission to view all resources.
6.0 Enforcement Architecture
Authorization checks will be performed at multiple layers of the application to create a defense-in-depth strategy.

6.1 API Route Middleware (Coarse-Grained)

Mechanism: An HTTP middleware will be applied to API routes or route groups. This is the first line of defense.
Implementation: The middleware will inspect the user's JWT rol claim (or perform a quick cache/DB lookup for the user's permissions) and check if the required permission is present. If not, it will immediately return a 403 Forbidden response.
Example (routes/api.php):
PHP

// Protect a single route
Route::post('/customers', [CustomerController::class, 'store'])->middleware('can:create:customer');

// Protect a group of routes
Route::middleware('can:view:report:financial')->group(function () {
    Route::get('/reports/mrr', [ReportController::class, 'mrr']);
    Route::get('/reports/ar-aging', [ReportController::class, 'arAging']);
});
6.2 Service Layer Checks (Fine-Grained)

Mechanism: For more complex business logic where authorization depends on the state of the data itself (also known as data-level security), checks will be performed inside the service layer.
Implementation: The application will use a "Policy" or "Gate" system. A Policy class is associated with a data model (e.g., InvoicePolicy).
Example (BillingService.php):
PHP

public function issueRefund(User $currentUser, Invoice $invoice, float $amount)
{
    // Check if the user is authorized to update THIS specific invoice
    if ($currentUser->cannot('update', $invoice)) {
        throw new AuthorizationException;
    }

    // Additional logic: A standard user cannot refund more than $100
    if ($amount > 100 && !$currentUser->hasPermissionTo('create:refund:large')) {
        throw new AuthorizationException('Refund amount exceeds limit.');
    }

    // ... proceed with refund logic
}
In this example, the InvoicePolicy might contain logic like: "A user can update an invoice only if it belongs to a customer in their assigned region."
6.3 Frontend UI Rendering (User Experience)

Mechanism: The frontend will conditionally render UI elements based on the user's permissions. This prevents users from even seeing buttons or links for actions they cannot perform.
Implementation:
Upon login, the user's complete list of permissions will be fetched and stored in the Redux store or a similar state management solution.
A custom React hook usePermissions() will be created to easily check for permissions within components.
Example (React Component):
React

import { usePermissions } from '@/hooks/use-permissions';

const CustomerDetailsPage = ({ customer }) => {
    const { hasPermission } = usePermissions();

    return (
        <div>
            <h1>{customer.name}</h1>
            {/* Only render the Delete button if the user has the permission */}
            {hasPermission('delete:customer') && (
                <Button variant="destructive">Delete Customer</Button>
            )}
        </div>
    );
};
Security Note: Hiding a button in the UI is for user experience only. It is not a security measure. The backend API endpoint must always enforce the permission check, as a malicious user can easily craft a direct API request.

7.0 Authorization Workflow Diagram
mermaid

graph TD
    subgraph Frontend
        A[User clicks "Create Invoice" button]
    end

    subgraph Backend
        B[API Request: POST /invoices] --> C{Route Middleware: `can:create:invoice`?}
        C -- Yes --> D[CustomerController.store()]
        D --> E[BillingService.createInvoice(user, data)]
        E --> F{Policy Check: `user->can('create', Invoice)`?}
        F -- Yes --> G[Create Invoice Logic...]
        G --> H[Return 201 Created]
        F -- No --> I[Throw AuthorizationException]
        C -- No --> I
        I --> J[Return 403 Forbidden]
    end
    
    A --> B
    H --> K[UI Updates]
    J --> K
8.0 Default Roles & Permissions (Initial Seed)
This serves as a starting point. The final list will be determined by stakeholders.

Role	Description	Key Permissions
Super Administrator	Unrestricted access. Manages users, roles, and system settings.	* (all permissions)
Company Owner	Read-only access to high-level financial and operational reports.	view:report:*, view:dashboard
Regional Manager	Manages a specific region. Can view regional reports and manage regional staff.	view:customer (scoped to region), view:report:regional, update:user (scoped to region)
Finance Department	Manages all billing and financial operations.	view:invoice, create:invoice, update:invoice, delete:invoice, view:payment, create:payment, create:refund
Customer Support	First line of contact for customers. Manages tickets and basic account info.	view:customer, update:customer:contact, view:invoice, view:payment, create:ticket, update:ticket
Sales Team	Manages leads and new customer sign-ups.	view:lead, create:lead, update:lead, create:customer
Installation Team	Manages installation work orders.	view:work-order, update:work-order, view:customer:contact_and_address
Network Engineer	Manages network infrastructure and provisioning.	view:tower, create:tower, view:mikrotik_router, execute:provisioning
Customer (Implicit)	Access to their own data via the customer portal.	(Handled by data-level policies, e.g., "A user can view an invoice only if they are the customer on that invoice.")
9.0 Risks
Risk	Description	Mitigation Strategy
Insecure Endpoints	A developer forgets to add an authorization check to a new API endpoint.	This is a critical risk. The default policy should be "deny all." A code review checklist must include "Verify authorization check." Automated static analysis tools can be configured to flag controller methods that lack an authorization middleware or policy check.
Permission Creep	Over time, users accumulate more permissions than they need, violating the principle of least privilege.	Regular (e.g., quarterly) audits of user roles and permissions should be conducted by the Super Administrator and department heads.
Performance Overhead	Complex permission checks, especially those requiring multiple database lookups, could slow down every request.	The user's roles and permissions will be aggressively cached. The rol claim in the JWT is the first layer of caching. For more complex permissions, a Redis cache will be used with a short TTL, invalidated on any role/permission change.
Complex Policy Logic	The logic for data-level security (Policies) becomes overly complex and hard to test.	Keep Policy classes focused and well-unit-tested. Avoid putting business logic in policies; they should only return true or false.
