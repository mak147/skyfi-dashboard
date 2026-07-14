Document 59: Development Roadmap
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Plan

1.0 Purpose
This document outlines the phased development roadmap for the initial build-out of the SkyFi Networks platform. It organizes the features defined in the Functional Requirements (Doc 03) into a logical sequence of development phases, culminating in the first production release (MVP) and subsequent core enhancements.

The purpose of this roadmap is to:

Provide a clear, high-level project plan for development teams.
Manage stakeholder expectations by defining what will be delivered and when.
Prioritize development to deliver the highest business value first.
Break down a large, complex project into manageable, incremental phases to mitigate risk.
This is a living document and may be adjusted based on development velocity, changing business priorities, and feedback from early testing.

2.0 Development Methodology
Agile/Scrum: The project will be executed using the Scrum framework.
Sprints: Development will be organized into two-week sprints.
Phases: This roadmap is organized into larger, thematic phases. Each phase will consist of multiple sprints. The end of each phase should ideally deliver a significant, cohesive block of functionality.
3.0 Roadmap Phases
The initial development is broken into three main phases, followed by a post-launch enhancement phase.

Phase 0: Foundation & Tooling (Internal)
Phase 1: MVP - The "Quote-to-Cash" Core
Phase 2: Operations & Network Automation
Phase 3: Enterprise Finance & Advanced Features
4.0 Phase 0: Foundation & Tooling (Sprints 1-3)
Goal: To establish the complete technical foundation, CI/CD pipelines, and core architectural patterns before feature development begins in earnest. This is a "sharpen the saw" phase.

Epic / Theme	Key User Stories / Tasks	Key Modules Affected
Project Setup	Initialize Frontend & Backend repositories with folder structures. Configure Docker for local development.	(All)
CI/CD Pipeline	Build initial CI/CD pipelines for both repos, including linting, testing, and build stages. Set up automated deployment to a "Dev" environment.	(DevOps)
Core Architecture	Implement core Authentication (Login, JWT) and Authorization (RBAC shells). Implement base API structure and AppException handler.	System, Shared
UI Foundation	Build the core Component Library (Buttons, Inputs, Modals, Layouts). Implement the Design System (colors, typography). Set up Storybook.	UI/UX, Frontend
Database Setup	Create initial database schema migrations for users, roles, permissions.	Database, System
Exit Criteria:

Developers can run the full application stack locally with a single docker-compose up command.
A PR to either repository automatically triggers a build and tests.
A basic, protected "Hello World" page is accessible after logging in.
The core component library is built and viewable in Storybook.
5.0 Phase 1: MVP - The "Quote-to-Cash" Core (Sprints 4-12)
Goal: To deliver the absolute minimum set of features required to sell a service, bill for it, and collect payment. This is the highest-value phase and represents our first major release candidate.

| Epic / Theme | Key User Stories / Tasks | Key Modules Affected | Priority |
| :--- | :--- | :--- | :--- | :--- |
| Customer & Sales| Create/View/Update Customers. Create/Manage Leads. Implement the full Customer 360° View shell. | CRM, Customer | High |
| Service Plans | CRUD for Service Plans. Define pricing and billing cycles. | Billing | High |
| Service Availability| Implement the core ServiceAvailabilityService. UI to check if an address is serviceable (simple radius-based check for MVP). | CRM, NMS | Medium |
| Quoting | Create, send, and track status of Quotes. Convert an accepted quote into a Customer. | CRM, Sales | High |
| Core Billing | Generate recurring and one-time invoices. Manually record payments. Apply credits. Generate PDF invoices. | Billing | High |
| Payment Gateway| Integrate Stripe for one-time payments via the Customer Portal. Store customer payment methods securely. | Billing, Payments | High |
| Customer Portal (MVP)| Customer login. View/download invoices. View payment history. Pay an invoice. Update profile/address. | Customer Portal | High |
| Support Ticketing (MVP)| Create/View/Reply to tickets. Staff and Customer Portal views. Basic status management. | Support | Medium |

Exit Criteria:

The system can successfully onboard a new customer from a quote.
The system can automatically generate a recurring invoice for that customer.
The customer can log into their portal and pay the invoice with a credit card.
A support agent can view the customer and their billing history to answer a question.
This version is a potential "go-live" candidate.
6.0 Phase 2: Operations & Network Automation (Sprints 13-20)
Goal: To build out the operational and network automation features that deliver major efficiency gains, moving beyond the core financial loop.

| Epic / Theme | Key User Stories / Tasks | Key Modules Affected | Priority |
| :--- | :--- | :--- | :--- | :--- |
| Installation Workflow| Create/Schedule/Assign Work Orders. Technician mobile-first view for completing jobs. | Operations | High |
| MikroTik Integration| Build the MikroTikAdapter. Implement logic for PPPoE and Hotspot user management. | NMS, PPPoE, Hotspot | High |
| Automated Provisioning| CRITICAL. Automate the link between Work Order completion and NMS service provisioning (WorkOrderCompleted event). | Operations, NMS | High |
| Automated Dunning| Implement the full dunning process: overdue notices, late fees, and automated service suspension/reconnection via NMS integration. | Billing, NMS | High |
| Core Inventory| Define inventory items (SKUs). Track serialized assets. Assign assets to technicians and installations. | Inventory | Medium |
| Tower Management| CRUD for Tower sites. View towers on a map. Associate network devices with towers. | NMS, Wireless | Medium |
| Core Reporting | Implement key operational reports: A/R Aging, New Activations, Subscriber Counts. | Reporting | Medium |

Exit Criteria:

The entire process from quote-to-installation-to-active service is automated.
A customer who doesn't pay is automatically suspended from the network without human intervention.
Technicians can manage their daily jobs through the application.
Basic inventory tracking is functional.
7.0 Phase 3: Enterprise Finance & Advanced Features (Sprints 21-28)
Goal: To complete the feature set, turning the platform into a true, all-in-one ERP system with advanced financial and analytical capabilities.

| Epic / Theme | Key User Stories / Tasks | Key Modules Affected | Priority |
| :--- | :--- | :--- | :--- | :--- |
| Purchasing | Create, approve, and manage Purchase Orders. | Purchasing | Medium |
| Vendor Management| Manage vendor records and contacts. | Vendor | Medium |
| Inventory Receiving| Integrate PO fulfillment with inventory stock updates (receiveStock). | Inventory, Purchasing| Medium |
| Full Finance Module| Implement the double-entry accounting system: Chart of Accounts, Journal Entries, General Ledger. | Finance | High |
| Financial Reporting| Generate core financial statements: Balance Sheet and Income Statement. | Reporting, Finance | High |
| Analytics Dashboard| Build the v1.0 role-based analytics dashboards. | Analytics | Medium |
| Audit Logging | Implement the full audit logging system for tracking all sensitive actions. | System, Audit | High |

Exit Criteria:

The system can track inventory from purchase to deployment.
All financial transactions are recorded in a double-entry ledger.
Administrators have a full audit trail of system activity.
The platform now largely replaces the need for separate accounting, CRM, and inventory software.
8.0 Visual Roadmap
mermaid

gantt
    title SkyFi Networks Development Roadmap
    dateFormat  YYYY-MM-DD
    axisFormat %b %Y
    
    section Phase 0: Foundation
    Project Setup & CI/CD :done, p0_1, 2024-01-01, 3w
    Core Arch & UI Lib  :done, p0_2, after p0_1, 3w

    section Phase 1: MVP (Quote-to-Cash)
    CRM & Billing Core      :active, p1_1, after p0_2, 6w
    Payment Gateway & Portal :active, p1_2, after p1_1, 6w
    Support System (MVP)    :p1_3, after p1_1, 4w
    
    section Phase 2: Operations & NMS
    Workflow & NMS Core     :p2_1, after p1_2, 6w
    Auto-Provisioning & Dunning :p2_2, after p2_1, 6w
    Inventory & Reporting (Core):p2_3, after p2_1, 4w

    section Phase 3: Enterprise ERP
    Purchasing & Vendor     :p3_1, after p2_2, 4w
    Full Finance Module     :p3_2, after p3_1, 6w
    Audit & Analytics       :p3_3, after p3_2, 4w
