Document 28: Customer Management
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture and functional design for the Customer Management module. It covers the entire customer lifecycle, from creation and onboarding to ongoing management and eventual offboarding. The centerpiece of this module is the "Customer 360° View," which consolidates all customer-related information into a single, unified interface.

The purpose is to design a system that serves as the single source of truth for all customer data, empowering sales, support, finance, and technical teams to have a complete and consistent understanding of every customer.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the customer data model, service interactions, and the architecture of the 360° view.
Backend Developers	Implement the CustomerService, related data models, and the API endpoints for managing customers.
Frontend Developers	Build the Customer 360° user interface, including all tabs, data displays, and interactive components.
Product Owner/Dept. Heads	Define what information is most critical for their teams to see on the customer overview.
3.0 Core Concepts
The Customer Entity: The customer is the central pivot entity in the entire system. Almost every other major entity (Invoices, Services, Tickets, Payments) has a direct relationship with it.
Customer 360° View: This is not just a single page but a design philosophy. From a single customer's record, an authorized user should be able to navigate to and view every piece of data related to that customer.
Customer Lifecycle: The system must manage the customer through a clearly defined lifecycle, represented by a status field. This status dictates what actions can be performed and what services (like billing) are active.
4.0 Customer Lifecycle & Statuses
The customers.status field is critical for driving automated system behavior.

Status	Trigger	System Behavior	Next Status(es)
pending	Customer record created (e.g., from an accepted quote).	Not billable. Service is not active. Awaiting installation.	active, inactive
active	Installation work order completed and service provisioned.	Included in recurring billing runs. Service is active on the network.	suspended, inactive
suspended	Automatically triggered by the Dunning process for non-payment. Can also be set manually.	Excluded from new recurring billing. Service is disabled on the network.	active (on payment), inactive
inactive	Customer cancels all services, or an admin manually deactivates the account.	Excluded from all billing. Services are de-provisioned. Data is retained for reporting.	(Terminal state)
State Transition Diagram:

mermaid

stateDiagram-v2
    [*] --> pending: Customer Created
    pending --> active: Installation Complete
    pending --> inactive: Canceled before Install
    
    active --> suspended: Non-Payment
    active --> inactive: Service Canceled
    
    suspended --> active: Payment Received
    suspended --> inactive: Canceled while Suspended
    
    inactive --> [*]
5.0 Architecture of the Customer 360° View
The Customer 360° View will be a multi-tabbed interface, designed to present a high density of information in an organized way. The URL will be /customers/{id}/{tab?}.

5.1 Header / Summary Pane

This section is always visible at the top of the page, regardless of the selected tab. It provides the most critical, at-a-glance information.

Content:
Customer Name
Customer ID
Status Badge (e.g., Active, Suspended - with appropriate color coding)
Primary Contact Info (Email, Phone)
Service Address
Key Financial Metric: Current Balance (sum of all unpaid invoice balances).
Primary Action Buttons (e.g., "Add Service", "Create Invoice", "Create Ticket"). These are role-aware.
5.2 Tabbed Interface

Each tab focuses on a specific domain of the customer's data. Each tab's content is fetched via a dedicated API call, ensuring the initial page load is fast and data is loaded on demand.

Tab Name	Content / Key Components	Required Permission	API Endpoint
Overview	A dashboard summarizing key info: list of active services, recent invoices, recent payments, recent tickets, and a notes/activity feed.	view:customer	GET /customers/{id}/overview
Services	A detailed list of all current and past services subscribed to by the customer. Allows for managing services (provisioning, status changes).	view:service	GET /customers/{id}/services
Invoices	A data table listing all invoices for the customer, with statuses and amounts. Links to individual invoice detail pages.	view:invoice	GET /customers/{id}/invoices
Payments	A data table listing all payments made by the customer, including method, date, and amount.	view:payment	GET /customers/{id}/payments
Tickets	A data table listing all support tickets associated with the customer.	view:ticket	GET /customers/{id}/tickets
Network	Displays technical details for the customer's active service: assigned tower, CPE info, real-time signal strength, latency graphs, active PPPoE session info.	view:customer:network-details	GET /services/{service_id}/status
Audit Log	A chronological, read-only log of all significant changes made to the customer's account and associated resources.	view:audit-log:customer	GET /customers/{id}/audit-log
Settings	Forms for editing customer details, addresses, and billing settings.	update:customer	(Uses standard GET /customers/{id} and PUT /customers/{id})
Frontend Implementation:

The tab selection will be controlled by the URL (e.g., /customers/123/invoices). This makes specific views linkable.
React Router's nested routing capabilities are perfect for this.
Each tab's content will be a separate component that uses its own useQuery hook from TanStack Query, ensuring data is fetched independently.
6.0 Core Backend Services (Crm Module)
6.1 CustomerService

Responsibility: Manages the core business logic for the customer entity itself.
Key Methods:
createCustomer(data): Creates a new customer, their address, and fires a CustomerCreated event.
updateCustomer(customer, data): Handles updating customer details.
changeCustomerStatus(customer, newStatus): The central method for managing the lifecycle. It contains a state machine logic to ensure valid transitions (e.g., you can't go from pending to suspended). It will dispatch events like CustomerSuspended or CustomerActivated.
calculateCurrentBalance(customer): A read-only method that queries all unpaid invoices for a customer and returns the total balance due.
6.2 API Endpoints

POST /api/v1/customers: Creates a new customer.
GET /api/v1/customers: Returns a paginated list of customers, with filtering and sorting capabilities.
GET /api/v1/customers/{id}: Returns the core data for a single customer (for the header and settings tab).
PUT /api/v1/customers/{id}: Updates a customer's core data.
DELETE /api/v1/customers/{id}: Soft-deletes a customer.
Tab-specific Endpoints: As listed in the table in section 5.2. These are designed to be efficient and return only the data needed for a specific tab. For example, GET /customers/{id}/invoices returns a list of invoices, not the entire customer object again.
7.0 Data Model Considerations
Soft Deletes: The customers table must use soft deletes (deleted_at column). We can never permanently lose a customer's record, as their financial history is critical for reporting.
Indexing: The customers table will be one of the most frequently queried tables. It needs indexes on status, last_name, and email to support fast searching and filtering.
Relationships: The customer_id foreign key is the linchpin of the entire database schema. All queries for related data must be efficient.
8.0 Customer Portal (External User Perspective)
The customer portal provides a simplified, self-service version of the Customer 360° view.

Authentication: The customer authenticates as themselves. All API calls are implicitly scoped to their own customer_id by the backend policies. A customer can never request /api/v1/customers/another-id.
Views: Provides simplified versions of the same tabs:
Overview: My Services, My Balance.
Billing: View and pay invoices, see payment history, manage payment methods.
Support: Open new tickets, view existing tickets.
Profile: Update contact and address information.
9.0 Risks
Risk	Description	Mitigation Strategy
Data Overload / Slow Performance	The Customer 360° view tries to load too much data at once, making the page slow and unresponsive.	The tabbed, on-demand data fetching architecture is the primary mitigation. Each tab's API endpoint must be optimized. The "Overview" tab should only show a summary (e.g., the 5 most recent invoices), not the entire history.
Inconsistent Data	Information shown in one tab (e.g., total balance on Overview) doesn't match the detailed view in another tab (e.g., sum of invoices in the Invoices tab).	Have a single source of truth for all calculations. The CustomerService::calculateCurrentBalance method should be the only place this logic lives. Different API endpoints should call this same service method to get the value, ensuring consistency. Use aggressive cache invalidation in TanStack Query to ensure data is refetched after any action that could change it.
Authorization Leaks	A user (e.g., Sales Agent) is able to see sensitive information (e.g., detailed network stats) that they shouldn't have access to.	Every single API endpoint, especially the tab-specific ones, must be protected by the appropriate permission check (can:view:invoice, can:view:customer:network-details, etc.). This must be enforced on the backend without exception.
Complex State Management	The frontend state for the 360° view becomes a complex, monolithic object that is hard to manage.	The combination of URL-driven routing for tabs and TanStack Query's component-level data fetching avoids this. Each tab is a self-contained unit that manages its own server state, keeping the global state minimal.