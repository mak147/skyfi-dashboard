Document 16: Navigation Structure
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the primary navigation structure for the SkyFi Networks staff administration portal. It outlines the layout of the main menu, sub-menus, and user-specific navigation items. The goal is to create an information architecture that is intuitive, logical, and role-aware.

A well-designed navigation structure is critical for:

Usability: Helping users find what they need quickly and efficiently.
Discoverability: Exposing the system's features in a logical way.
Efficiency: Reducing the number of clicks required to complete common tasks.
Scalability: Providing a framework that can accommodate new features without becoming cluttered.
2.0 Responsibilities
Role	Responsibility
Principal Architect / UX Designer	Define the overall navigation hierarchy and patterns.
Frontend Developers	Implement the navigation components (sidebar, top bar) and routing logic. Ensure the structure is data-driven by user permissions.
Product Owner	Validate that the navigation structure aligns with user workflows and priorities.
QA Engineers	Test navigation paths for all user roles, ensuring links are correct and permissions are respected.
3.0 Architectural Principles
Role-Based Rendering: The navigation is dynamic. Users will only see links to pages and sections for which they have the required view permission. This is a direct frontend implementation of the RBAC system.
Primary and Secondary Navigation: The structure will use a primary vertical sidebar for main application modules and a secondary horizontal top bar for user-specific actions, search, and notifications. This is a common and effective pattern in modern dashboards (e.g., Stripe, Vercel).
Hierarchy and Grouping: Related items will be grouped together under logical headings or collapsible sub-menus to reduce cognitive load.
Consistency: The placement of navigation elements will be consistent across the entire application.
Task-Oriented: The structure is organized around user tasks and business domains (e.g., "Billing", "Sales"), not just data models.
4.0 Navigation Layout
The main application layout will consist of two persistent navigation areas:

Primary Navigation (Left Sidebar): A collapsible vertical menu containing links to the main modules and sub-pages of the application.
Secondary Navigation (Top Bar): A horizontal bar at the top of the page containing a global search, notification center, user profile menu, and quick-action buttons.
Layout Diagram:

mermaid

graph TD
    subgraph "Application Viewport"
        TopBar["Top Bar (Global Search, Notifications, User Menu)"]
        
        subgraph "Main Content Area"
            Sidebar["Left Sidebar (Main Navigation)"]
            Content["Page Content (e.g., Customer List)"]
        end

        Sidebar -- "Controls" --> Content
        TopBar -- "Global Actions" --> App
    end
    
    style TopBar fill:#f0f0f0,stroke:#333
    style Sidebar fill:#e0e0e0,stroke:#333
    style Content fill:#fafafa,stroke:#333
5.0 Primary Navigation (Sidebar) Structure
This section details the menu items that will appear in the left sidebar. Each item will only be rendered if the user has the corresponding view permission for that resource or module.

Structure:

Menu Group
Menu Item -> /route (required permission)
Sub-Menu Item -> /route/sub-route (required permission)
[Dashboard]

Dashboard -> /dashboard (view:dashboard)
[Sales & Customers]

Customers -> /customers (view:customer)
Leads -> /leads (view:lead)
Quotes -> /quotes (view:quote)
Service Availability -> /service-availability (execute:service-availability-check)
[Finance]

Invoices -> /invoices (view:invoice)
Payments -> /payments (view:payment)
Credits -> /credits (view:credit)
Service Plans -> /service-plans (view:service-plan)
Financial Reports (view:report:financial)
MRR -> /reports/financial/mrr
AR Aging -> /reports/financial/ar-aging
Payments Collected -> /reports/financial/payments
[Operations]

Work Orders -> /work-orders (view:work-order)
Scheduler -> /scheduler (view:work-order)
[Support]

Tickets -> /tickets (view:ticket)
Knowledge Base -> /kb (view:kb)
[Network]

Network Dashboard -> /network/dashboard (view:network-status:global)
Towers -> /network/towers (view:tower)
Routers -> /network/routers (view:mikrotik-router)
IP Management -> /network/ipam (view:ip-address-pool)
Network Reports (view:report:network)
Uptime -> /reports/network/uptime
Capacity -> /reports/network/capacity
[Inventory]

Items -> /inventory/items (view:inventory-item)
Warehouses -> /inventory/warehouses (view:warehouse)
Purchase Orders -> /inventory/purchase-orders (view:purchase-order)
Vendors -> /inventory/vendors (view:vendor)
[Administration] (view:administration)

User Management -> /admin/users (manage:user)
Role Management -> /admin/roles (manage:role)
System Settings -> /admin/settings (manage:settings)
Audit Log -> /admin/audit-log (view:audit-log)
6.0 Secondary Navigation (Top Bar) Structure
This bar provides access to global, non-module-specific functionality.

6.1 Elements (Left to Right)

Global Search Bar:

Purpose: A single input field to quickly find resources across the entire system.
Functionality: Typing "John Doe" could return results for the customer record, any invoices for John Doe, and any support tickets opened by him.
Implementation: Requires a dedicated backend search endpoint (GET /api/v1/search?q=...) that queries multiple tables. This is a complex feature that may be simplified in Phase 1 (e.g., only search customers).
"Quick Create" Button:

Purpose: A + button that opens a dropdown menu for creating new resources quickly from anywhere in the app.
Dropdown Items (Role-Based):
New Customer (create:customer)
New Invoice (create:invoice)
New Ticket (create:ticket)
New Lead (create:lead)
Notification Center:

Purpose: An icon (e.g., a bell) that indicates new system notifications.
Functionality: Clicking the icon opens a dropdown listing recent, relevant events, such as "@JohnDoe mentioned you in a ticket" or "Payment received from Customer #1234".
Implementation: Requires a notifications table and a WebSocket or polling mechanism for real-time updates.
User Profile Menu:

Purpose: A dropdown menu activated by clicking the user's name or avatar.
Dropdown Items:
User's Name & Role (display only)
My Profile -> /profile (Link to a page where the user can change their own name, password, etc.)
Theme (Light/Dark) (Toggle)
Logout (Executes the logout action)
7.0 Navigation by Role (Examples)
This demonstrates how the navigation would dynamically change for different users.

Example 1: Customer Support Role
A support agent would see a sidebar like this:

Dashboard
Customers
Invoices (view only)
Payments (view only)
Tickets
Knowledge Base
They would not see Finance Reports, Network, Inventory, or Administration.

Example 2: Network Engineer Role
A network engineer's sidebar would be focused:

Dashboard
Network
Network Dashboard
Towers
Routers
IP Management
Network Reports
Tickets (view only, to see network-related issues)
They would not see Sales, Finance, or Inventory.

8.0 Breadcrumbs
Purpose: To provide contextual awareness of the user's location within the application hierarchy.
Location: Displayed at the top of the main content area, below the top bar.
Example: On a specific invoice page, the breadcrumbs would show: Finance > Invoices > INV-2023-00123
Implementation: The breadcrumb component will read the current route and generate the links dynamically.
9.0 Implementation Notes
Data-Driven from a Config File: The entire sidebar structure will be defined in a TypeScript configuration file (e.g., src/config/navigation.ts). This file will map routes, labels, icons, and required permissions.
Recursive Rendering: A React component will recursively render the navigation structure from this config file, performing a permission check for each item before rendering it. This makes the navigation system highly maintainable and easy to update.
Active State: The navigation component must clearly indicate the user's current location by highlighting the active menu item. This will be managed by comparing the current route path with the links in the navigation config.
10.0 Risks
Risk	Description	Mitigation Strategy
Cluttered Navigation	As more features are added, the sidebar becomes overwhelmingly long and complex.	Good initial grouping is key. A "less is more" approach should be taken. If a page is rarely used, consider if it needs to be in the primary navigation or can be linked from a more relevant page. The collapsible nature of menu groups also helps.
Inconsistent Permissions	A user can see a link in the navigation but gets a "403 Forbidden" error when they click it.	The permission string in the navigation config (view:customer) must exactly match the permission being checked in the backend API middleware. This requires close coordination and consistent naming. A comprehensive test plan for each role is essential to catch these mismatches.
Poor Mobile Experience	The sidebar/top bar layout does not work well on smaller screens (e.g., a technician on a tablet).	The design must be responsive. On smaller viewports, the sidebar should automatically collapse into a "hamburger" menu, and the top bar elements may be consolidated. This is a standard requirement for the UI/UX design.