Document 03: Functional Requirements
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the functional requirements for the SkyFi Networks ISP Management System. It defines what the system must do from a user's perspective. These requirements form a contract between stakeholders and the development team, detailing the specific behaviors, capabilities, and functions the software must provide.

Each requirement is uniquely identified, testable, and serves as a direct input for user story creation, development tasks, and Quality Assurance (QA) test cases. This document intentionally avoids how the system will be implemented, which is detailed in subsequent architectural documents.

2.0 Responsibilities
Role	Responsibility
Product Owner	Own and prioritize these functional requirements. Act as the final authority on functional scope and acceptance criteria.
Business Stakeholders	Review and validate that these requirements accurately reflect their departmental needs and workflows.
Principal Architect	Ensure the requirements are technically feasible and align with the overarching architecture.
Development Team	Implement the software to meet these specified functional requirements.
QA Team	Develop test plans and test cases to verify that each functional requirement has been successfully implemented.
3.0 Goals
The overarching goal of these functional requirements is to translate the Product Vision into a concrete, actionable, and verifiable set of system capabilities. They aim to:

Provide Clarity: Eliminate ambiguity about the system's expected behavior for all teams.
Define Scope: Clearly delineate the features included in the initial and subsequent phases of development.
Enable Traceability: Create a traceable link from business need -> functional requirement -> user story -> code -> test case.
Guide Development: Serve as the primary source of truth for developers when implementing features.
Facilitate Testing: Provide a clear, objective basis for all QA and User Acceptance Testing (UAT) activities.
4.0 Functional Requirements Specification
The functional requirements are organized by system module. Each requirement has a unique ID for traceability (e.g., FR-MOD-XXX) and is categorized by priority based on the phased implementation plan from the Product Vision (P1, P2, P3).

Priority Legend:

[P1]: Phase 1 (MVP) - Core functionality for initial launch.
[P2]: Phase 2 - Operational and core network automation.
[P3]: Phase 3 - Enterprise finance and advanced features.
4.1 Module: System & Administration (SYS)

ID	Requirement	Priority
FR-SYS-001	The system must provide a user management interface for creating, viewing, updating, and deactivating user accounts.	[P1]
FR-SYS-002	The system must allow administrators to assign one or more roles to a user account.	[P1]
FR-SYS-003	The system must provide a role and permission management interface for defining roles and the specific actions they can perform.	[P1]
FR-SYS-004	The system must log all security-sensitive events in an immutable audit log (e.g., login, logout, password change, role change).	[P1]
FR-SYS-005	The system must allow configuration of global settings, such as company name, address, logo, and default currency.	[P1]
FR-SYS-006	The system must support the definition of multiple geographical regions to segment customers, staff, and network infrastructure.	[P3]
4.2 Module: CRM & Sales (CRM)

ID	Requirement	Priority
FR-CRM-001	The system must allow a Sales user to create and manage 'Lead' records with contact information, status, and source.	[P1]
FR-CRM-002	The system must allow a 'Lead' to be converted into a 'Customer' account, automatically transferring relevant contact information.	[P1]
FR-CRM-003	The system must provide a unified 'Customer' view that displays all associated data: contact details, service plans, invoices, payments, support tickets, and notes.	[P1]
FR-CRM-004	The system must store and manage customer address information, including a primary service address and a separate billing address.	[P1]
FR-CRM-005	The system must allow authorized users to add, edit, and view timestamped notes on a customer's record.	[P1]
FR-CRM-006	The system must integrate with a mapping service to perform a 'Service Availability Check' based on an address, determining if it falls within a defined service area.	[P1]
FR-CRM-007	The system must allow a Sales user to generate a 'Quote' for a potential customer, detailing selected service plans, one-time fees, and monthly recurring costs.	[P1]
FR-CRM-008	The system must allow a Quote to be sent to a customer via email and track its status (e.g., Draft, Sent, Accepted, Declined).	[P1]
FR-CRM-009	Upon quote acceptance, the system must allow the user to generate a 'Contract' from a predefined template.	[P1]
4.3 Module: Billing & Finance (BIL)

ID	Requirement	Priority
FR-BIL-001	The system must allow administrators to define 'Service Plans' with attributes: name, price, billing cycle (monthly, quarterly, yearly), and associated network service profile (e.g., speed).	[P1]
FR-BIL-002	The system must allow administrators to define one-time charges and discounts (e.g., Installation Fee, Activation Fee, First Month Free).	[P1]
FR-BIL-003	The system must automatically generate recurring invoices for all active customers on their scheduled billing cycle date.	[P1]
FR-BIL-004	The system must automatically calculate prorated charges for services activated or terminated mid-billing cycle.	[P1]
FR-BIL-005	The system must automatically calculate prorated charges/credits for mid-cycle service plan upgrades or downgrades.	[P2]
FR-BIL-006	The system must allow authorized users to manually create an invoice for a customer.	[P1]
FR-BIL-007	The system must record payments against invoices, supporting payment methods such as Credit Card (via Gateway), Bank Transfer, and Cash.	[P1]
FR-BIL-008	The system must integrate with a PCI-compliant Payment Gateway to process credit card payments. The system must not store full credit card numbers.	[P1]
FR-BIL-009	The system must support an automated 'Dunning' process for overdue invoices, including sending reminders, applying late fees, and ultimately triggering service suspension.	[P2]
FR-BIL-010	The system must allow authorized users to issue full or partial 'Refunds' against a recorded payment.	[P2]
FR-BIL-011	The system must allow authorized users to apply 'Credits' to a customer's account, which will be automatically applied to future invoices.	[P1]
FR-BIL-012	The system must generate PDF versions of all invoices, which can be downloaded by staff and customers.	[P1]
FR-BIL-013	The system must provide a General Ledger (GL) Chart of Accounts and associate all financial transactions (invoices, payments, refunds) with the appropriate GL codes.	[P3]
4.4 Module: Customer Portal (CP)

ID	Requirement	Priority
FR-CP-001	The system must provide a secure login for customers to access their personal portal.	[P1]
FR-CP-002	Customers must be able to view and update their personal contact and billing information.	[P1]
FR-CP-003	Customers must be able to view their current and past invoices and download them as PDFs.	[P1]
FR-CP-004	Customers must be able to view their payment history.	[P1]
FR-CP-005	Customers must be able to pay an outstanding invoice using a saved payment method or a new credit card via the integrated payment gateway.	[P1]
FR-CP-006	Customers must be able to create a new support ticket, view the status of existing tickets, and add comments.	[P1]
FR-CP-007	Customers must be able to view their current service plan and data usage (if applicable).	[P2]
4.5 Module: Network Management (NMS)

ID	Requirement	Priority
FR-NMS-001	The system must allow administrators to define and manage 'Tower' sites with details: name, location (GPS coordinates), height, and associated region.	[P2]
FR-NMS-002	The system must allow administrators to add network devices (e.g., MikroTik Routers) with IP address, API credentials, and associate them with a Tower.	[P2]
FR-NMS-003	The system must be able to connect to a MikroTik router via its API to perform management actions.	[P2]
FR-NMS-004	The system must automatically provision a PPPoE user on a designated MikroTik router when a customer's service is activated.	[P2]
FR-NMS-005	The provisioning action must set the PPPoE user's username, password, and assign a bandwidth profile (e.g., Simple Queue) that matches their service plan.	[P2]
FR-NMS-006	The system must automatically disable a PPPoE user on a MikroTik router when their service is suspended due to non-payment.	[P2]
FR-NMS-007	The system must automatically re-enable a PPPoE user when their suspended account is paid in full.	[P2]
FR-NMS-008	The system must allow a network engineer to view a list of active PPPoE sessions on a given router.	[P2]
FR-NMS-009	The system must manage a central pool of IP addresses (IPAM) and assign static IPs to customers as required.	[P3]
FR-NMS-010	The system must allow for the management of Hotspot users, plans, and vouchers on designated MikroTik routers.	[P2]
4.6 Module: Installation & Field Service (OPS)

ID	Requirement	Priority
FR-OPS-001	The system must automatically generate an 'Installation Work Order' when a new customer contract is finalized.	[P2]
FR-OPS-002	The work order must contain all relevant information: customer name, address, contact info, service plan, GPS coordinates, and required equipment.	[P2]
FR-OPS-003	The system must provide a calendar/scheduling interface to assign work orders to available installation technicians.	[P2]
FR-OPS-004	Technicians must be able to view their assigned work orders in a list or on a map.	[P2]
FR-OPS-005	Technicians must be able to update the status of a work order (e.g., Scheduled, In Progress, On Hold, Completed, Failed).	[P2]
FR-OPS-006	Upon completion, the technician must be able to enter notes, capture signal strength readings, and record the serial numbers of installed equipment (CPE).	[P2]
FR-OPS-007	Marking a work order as 'Completed' must trigger the service activation and billing initiation process.	[P2]
4.7 Module: Support & Ticketing (SUP)

ID	Requirement	Priority
FR-SUP-001	The system must allow support staff and customers to create 'Support Tickets'.	[P1]
FR-SUP-002	Each ticket must have a unique ID, associated customer, status (e.g., Open, In Progress, Awaiting Customer, Closed), priority, and assigned agent.	[P1]
FR-SUP-003	The system must allow users to add public or private comments/notes to a ticket.	[P1]
FR-SUP-004	The system must send email notifications to the customer and assigned staff upon ticket creation and updates.	[P1]
FR-SUP-005	The system must provide a searchable and filterable list view of all support tickets.	[P1]
FR-SUP-006	The system must allow a support ticket to be converted into a 'Field Technician Work Order' if an on-site visit is required.	[P2]
4.8 Module: Inventory & Purchasing (INV)

ID	Requirement	Priority
FR-INV-001	The system must allow for the definition of inventory 'Items' with attributes like SKU, name, description, and supplier.	[P2]
FR-INV-002	The system must track stock levels of each item in multiple 'Warehouses' or locations (including technician vehicles).	[P2]
FR-INV-003	The system must track individual 'Assets' by serial number for key equipment like CPEs and routers.	[P2]
FR-INV-004	The system must allow an asset to be associated with a customer account upon installation.	[P2]
FR-INV-005	The system must provide functionality to create and manage 'Vendors' (suppliers) with their contact and payment information.	[P3]
FR-INV-006	The system must allow users to create, approve, and send 'Purchase Orders' to vendors.	[P3]
FR-INV-007	The system must allow users to record the receipt of items from a purchase order, updating stock levels accordingly.	[P3]
4.9 Module: Reporting & Analytics (REP)

ID	Requirement	Priority
FR-REP-001	The system must provide a main dashboard with key performance indicators (KPIs), such as Active Subscribers, Revenue, Overdue Invoices, and Open Tickets.	[P1]
FR-REP-002	The system must generate standard financial reports, including Monthly Recurring Revenue (MRR), Accounts Receivable (A/R) Aging, and Payments Collected.	[P2]
FR-REP-003	The system must generate reports on subscriber activity, including new activations, terminations, and churn rate.	[P2]
FR-REP-004	The system must generate reports on network health, including tower capacity and device status.	[P3]
FR-REP-005	All reports must be filterable by date ranges and region.	[P2]
FR-REP-006	The system must allow specified reports to be exported in CSV or PDF format.	[P2]
5.0 Architecture & Workflow Impact
These functional requirements necessitate an architecture where modules are highly interconnected. A change in one module often triggers a workflow in another.

mermaid

graph TD
    subgraph CRM
        A[FR-CRM-002: Convert Lead to Customer]
    end

    subgraph OPS
        B[FR-OPS-001: Generate Installation Work Order]
    end

    subgraph BIL
        C[FR-BIL-003: Initiate Billing Cycle]
    end

    subgraph NMS
        D[FR-NMS-004: Provision PPPoE User]
    end

    subgraph INV
        E[FR-INV-004: Assign Asset to Customer]
    end

    A --> B
    B -- Installation Complete --> C
    C -- Service Active --> D
    B -- Equipment Installed --> E

    style A fill:#cde4ff
    style B fill:#e6ffc2
    style C fill:#fff4c2
    style D fill:#ffc2c2
    style E fill:#d4c2ff
Diagram Explanation: This diagram illustrates a critical workflow. Converting a Lead (CRM) functionally requires the creation of a Work Order (OPS). The completion of that Work Order functionally requires the initiation of a billing cycle (BIL) and the provisioning of a network service (NMS), which itself requires assigning inventory assets (INV). The architecture must support these cross-module event-driven workflows.

6.0 Business & Validation Rules
Implicit Requirement: Every functional requirement that involves data entry (e.g., FR-CRM-001, FR-BIL-007) implies the existence of data validation as specified in the "Validation Standards" document.
Business Rule Enforcement: The implementation of these functions must enforce the business rules defined in the Product Vision. For example, FR-CRM-006 (Service Availability Check) must functionally prevent a quote FR-CRM-007 from being created if a tower is at capacity.
7.0 Security & Permissions
Implicit Requirement: Every functional requirement is subject to the Role-Based Access Control (RBAC) system. The ability to execute any function defined in this document must be a distinct, grantable permission.
Example: FR-BIL-010 (Issue Refund) must only be accessible to users whose role contains the refund:create permission. This must be enforced at the API level.
8.0 Future Expansion
The modular breakdown of these requirements is intentional. It allows for future expansion. For instance, a new module, "VoIP Management," could be added with its own set of FR-VOIP-XXX requirements that integrate with the existing BIL and CRM modules.
The FR-NMS-XXX requirements are initially scoped to MikroTik. The underlying implementation must be abstract enough to accommodate future requirements for other vendors (e.g., FR-NMS-020: Provision Ubiquiti CPE).
9.0 Risks
Risk	Mitigation
Requirement Ambiguity	All requirements will be refined into detailed User Stories with clear Acceptance Criteria before a sprint begins. The Product Owner is the final arbiter.
Scope Creep	Any new functional requirement proposed after this document is approved must go through a formal change request process, evaluating its impact on schedule, cost, and alignment with the Product Vision.
"Gold Plating"	Developers must implement only what is specified in the requirements and associated user stories. Functionality not explicitly requested should not be built.
Integration Complexity	Requirements involving external systems (e.g., FR-BIL-008, FR-NMS-003) carry higher risk. Proof-of-concept spikes will be prioritized to de-risk these integrations early.
10.0 Implementation Notes
Agile Approach: This document provides the "what," not the "when." The Product Owner will prioritize these functional requirements into a product backlog. The development team will implement them iteratively in sprints.
Traceability Matrix: A traceability matrix will be maintained to map each functional requirement to its corresponding user stories, test cases, and code commits. This is crucial for validation and compliance in an enterprise environment.
This concludes Document 03: Functional Requirements.