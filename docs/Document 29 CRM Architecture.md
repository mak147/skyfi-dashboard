Document 29: CRM Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Customer Relationship Management (CRM) module within the SkyFi Networks platform. This module covers the "top of the funnel" activities, including lead management, sales pipeline tracking, quoting, and the seamless conversion of a prospect into a paying customer.

The purpose is to design an integrated CRM that streamlines the sales process, provides visibility into the sales pipeline, and automates the handoff from Sales to Operations, directly supporting the "Quote-to-Cash" vision.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models, service architecture, and workflows for the CRM module.
Backend Developers	Implement the LeadService, QuoteService, and related API endpoints.
Frontend Developers	Build the user interfaces for the sales pipeline (Kanban board), lead/quote management forms, and the service availability map.
Sales Team/Sales Manager	Act as the primary stakeholders, defining the stages of the sales pipeline and the information required to qualify and convert a lead.
3.0 Core CRM Concepts
Lead: A potential customer or prospect who has expressed interest but is not yet qualified.
Sales Pipeline: A visual representation of the stages a lead goes through from initial contact to a closed deal.
Service Availability Check: A critical tool for a WISP, allowing a sales agent to determine if a prospect's address can be serviced by the network.
Quote: A formal, non-binding offer of services and pricing sent to a qualified lead.
Conversion: The pivotal action where an "Accepted" quote is transformed into a Customer record, a Service subscription, and an Installation Work Order.
4.0 Sales Workflow & Data Flow
This workflow represents the ideal path from initial contact to a new, paying customer.

mermaid

flowchart TD
    A[New Lead Created] --> B{Qualify Lead}
    B -- "Interested" --> C[Service Availability Check]
    B -- "Not a Fit" --> Z[Mark as Unqualified]
    
    C -- "Address is Serviceable" --> D[Create Quote]
    C -- "Address is Not Serviceable" --> E[Add to Waitlist / Mark as Unqualified]
    
    D --> F[Send Quote to Prospect]
    F --> G{Prospect's Response}
    G -- "Accepts Quote" --> H[**Convert to Customer**]
    G -- "Declines Quote" --> I[Mark Quote as Declined]
    
    subgraph "Conversion Process"
        H --> H1[1. Create `Customer` record]
        H1 --> H2[2. Create `Service` record (status: 'pending')]
        H2 --> H3[3. Create `Installation Work Order`]
        H3 --> H4[4. Dispatch `NewCustomerOnboarding` Event]
    end

    H4 --> J[Operations team takes over]

    style H fill:#90EE90
5.0 CRM Module Architecture (Backend)
The CRM module (src/Crm/) will contain services and data models specific to the sales process.

5.1 Data Models

leads:
id, first_name, last_name, email, phone, address, source (e.g., 'Website', 'Referral'), status (ENUM: new, contacted, qualified, unqualified).
assigned_to_id (FK to users table for the sales agent).
quotes:
id, lead_id (FK), status (ENUM: draft, sent, accepted, declined, expired), expires_at (DATE).
quote_items:
Line items for a quote, mirroring invoice_items. E.g., "Home Basic 50/10 - $50.00/mo", "Installation Fee - $150.00 (One-Time)".
5.2 Core Services

LeadService

Responsibility: Manages leads and their progression through the pipeline.
Key Methods:
createLead(data, assigned_to)
changeLeadStatus(lead, newStatus)
assignLead(lead, user)
ServiceAvailabilityService

Responsibility: Encapsulates the logic for checking if an address is serviceable.
Key Method: check(latitude, longitude)
Logic:
This service queries the towers table and potentially a more complex coverage_maps table (storing GIS data like polygons).
It performs a geospatial query to determine if the given coordinates fall within a defined coverage area.
It also checks the capacity_utilization of the serving tower to ensure there is enough bandwidth to add a new customer.
Returns a result object: { isServiceable: boolean, servingTowerId: number | null, availablePlans: ServicePlan[] }.
QuoteService

Responsibility: Manages the creation and lifecycle of quotes.
Key Methods:
createQuote(lead, items)
sendQuoteToLead(quote): Generates a PDF of the quote and dispatches a notification via the NotificationService.
markAsAccepted(quote): Changes the quote status and triggers the conversion process.
ConversionService (Crucial Integration Service)

Responsibility: To handle the complex, transactional process of converting an accepted quote into a live customer record.
Key Method: convertQuoteToCustomer(quote)
Logic (wrapped in a single database transaction):
Call CustomerService->createCustomer() using data from the lead and quote.
For each recurring item on the quote, call SubscriptionService->createSubscription() to create the Service records. The billing_cycle_anchor_date is set here.
Call WorkOrderService->createInstallationOrder() using the customer ID, address, and required services.
Dispatch a NewCustomerOnboarding event, which can trigger welcome emails and other onboarding workflows.
If any step fails, the entire transaction is rolled back, preventing orphaned data.
6.0 Frontend User Interface
6.1 Sales Pipeline / Kanban Board

URL: /leads
Description: The primary view for the Sales Team. A multi-column board where each column represents a stage in the sales pipeline (New, Contacted, Qualified, Quote Sent).
Functionality:
Leads are represented as cards.
Sales agents can drag and drop cards between columns to update the lead's status.
Each card shows key information (name, date created) and is clickable to open the full Lead Detail View.
Filters will be available to show "My Leads" vs. "All Leads."
6.2 Lead/Quote Detail View

URL: /leads/{id}, /quotes/{id}
Description: A detailed view similar in layout to the Customer 360° view.
Components:
Header with Lead/Quote status.
Contact information and address.
Service Availability Tool: An embedded map (using Mapbox/Google Maps) showing the prospect's address and nearby towers. A button triggers the service availability check. The results are displayed clearly.
An activity timeline showing all communications (emails sent, notes added).
A section for managing the associated quote(s).
6.3 Quote Builder UI

Description: A form for creating a new quote.
Functionality:
Allows the sales agent to search for and add Service Plans as line items.
Allows adding custom one-time charges (e.g., "Custom Installation Work").
Calculates recurring monthly totals and one-time totals.
Provides a "Preview" option to see the PDF that the customer will receive.
7.0 API Endpoints
The CRM module will expose a standard set of RESTful endpoints:

GET /api/v1/leads: Get a list of leads (supports filtering by status and assigned user for the Kanban board).
POST /api/v1/leads: Create a new lead.
PATCH /api/v1/leads/{id}: Update a lead (e.g., change its status).
POST /api/v1/service-availability/check: Takes coordinates, returns serviceability status.
GET /api/v1/quotes: Get a list of quotes.
POST /api/v1/quotes: Create a new quote.
POST /api/v1/quotes/{id}/send: Action endpoint to send the quote.
POST /api/v1/quotes/{id}/convert: The critical action endpoint. Triggers the ConversionService to convert the quote into a customer.
8.0 Integration with Other Modules
The CRM module is an "upstream" module that feeds data into the rest of the system. Its integration points are critical.

Customer Management: The ConversionService is the primary integration point, creating records in the Customer domain.
Billing: The ConversionService also creates the initial Service subscriptions, which are the core entities for the Billing module.
Operations: The ConversionService creates the Installation Work Order, initiating the operational workflow.
Notifications: The QuoteService uses the NotificationService to email quotes to prospects.
9.0 Risks
Risk	Description	Mitigation Strategy
Flaw in Conversion Logic	A bug in the ConversionService creates partial data (e.g., a customer is created but the work order is not), breaking the entire onboarding flow.	The entire conversion process must be wrapped in a single, robust database transaction. The service must be heavily unit- and integration-tested, covering all failure scenarios.
Inaccurate Service Availability	The ServiceAvailabilityService gives a false positive (says a location is serviceable when it's not), leading to a failed installation and an angry customer.	The underlying coverage map data must be meticulously maintained by the Network Engineering team. The service should be conservative in its estimates and could include a "confidence score" in its response.
Complex UI	The Kanban board or Quote Builder becomes cluttered and hard for sales agents to use.	Follow the UI/UX Design System principles strictly. Use progressive disclosure to hide advanced options. Conduct usability testing sessions with the sales team early and often during development.
Sales/Support Data Silo	A sales agent has a conversation with a lead, but that information is not visible after the lead becomes a customer.	During the conversion process, all notes and significant activity from the lead's record should be copied over to the new customer's notes/activity feed to provide a seamless history for the support team.