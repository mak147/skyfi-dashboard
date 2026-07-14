Document 38: Support Ticket System
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the integrated Support Ticket System within the SkyFi Networks platform. This module provides the tools for creating, managing, tracking, and resolving customer support requests.

The goal is to design a ticketing system that is:

Integrated: Deeply connected to the Customer, Billing, and Network modules to provide support agents with complete context.
Efficient: Streamlines the workflow for support agents to resolve issues quickly.
Multi-channel: Capable of ingesting tickets from various sources (customer portal, email, phone).
Collaborative: Allows for internal notes and assignments between team members and departments.
Customer-centric: Gives customers visibility into the status of their requests via the customer portal.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the data models, state machine, and service architecture for the ticketing system.
Backend Developers	Implement the TicketService, API endpoints, and email ingestion logic.
Frontend Developers	Build the ticket list and detail view interfaces for both staff and the customer portal.
Customer Support Manager	Primary Stakeholder. Define ticket statuses, priorities, and the desired workflow for the support team.
3.0 Core Concepts
Ticket: The central record for a single customer issue or request.
Ticket Status: A field that tracks the ticket's position in the resolution workflow (e.g., Open, In Progress, Closed).
Priority: A classification of the ticket's urgency (e.g., Low, Normal, High, Urgent).
Assignment: A ticket is assigned to a specific user (agent) or a team (queue).
Response/Reply: A communication entry on a ticket, which can be public (visible to the customer) or internal (visible only to staff).
Knowledge Base (KB): A repository of articles and solutions that can be used to resolve common issues.
4.0 Ticketing Workflow
mermaid

flowchart TD
    subgraph "Ticket Creation Channels"
        A[Customer creates via Portal]
        B[Support Agent creates via Phone Call]
        C[System ingests from support@skyfi.com email]
    end

    subgraph "Ticketing System"
        D[New Ticket Created (Status: 'Open')]
        D --> E{Triage & Assign}
        E --> F[Assign to Agent/Team]
        F --> G[Status: 'In Progress']
        G --> H{Agent Investigates & Communicates}
        H --> I[Add Public/Internal Replies]
        
        subgraph "Resolution Paths"
            H --> J[Resolved via Communication]
            H --> K[Link to Knowledge Base Article]
            H --> L[Escalate / Create Work Order]
        end

        J --> M[Status: 'Resolved']
        K --> M
        L -- Generates Work Order --> M

        M --> N{Awaiting Customer Confirmation}
        N -- "Customer Confirms" or "Auto-close after X days" --> O[Status: 'Closed']
        N -- "Customer Replies 'Not Fixed'" --> G
    end

    A --> D
    B --> D
    C --> D
5.0 Data Model Architecture
5.1 support_tickets Table

Column	Type	Description
id	BIGINT	PK: Unique identifier.
ticket_uid	VARCHAR(20)	UK: A human-readable ticket ID (e.g., "TKT-2023-ABC12").
customer_id	BIGINT	FK: The customer this ticket belongs to.
status	ENUM(...)	open, in_progress, on_hold, awaiting_customer, resolved, closed.
priority	ENUM(...)	low, normal, high, urgent.
subject	VARCHAR(255)	The title of the ticket.
assigned_to_user_id	INT	FK (Nullable): The specific agent assigned to the ticket.
assigned_to_team_id	INT	FK (Nullable): The team/queue the ticket is in.
source	ENUM(...)	portal, email, phone, system.
5.2 ticket_replies Table

Column	Type	Description
id	BIGINT	PK
ticket_id	BIGINT	FK: The ticket this reply belongs to.
user_id	INT	FK (Nullable): The staff member who wrote the reply.
customer_id	BIGINT	FK (Nullable): The customer who wrote the reply (from the portal).
body	TEXT	The content of the reply.
type	ENUM('public', 'internal')	Determines visibility. public is visible to the customer.
created_at	TIMESTAMP	The time the reply was posted.
5.3 knowledge_base_articles Table

id, title, slug, content (Markdown/HTML), category_id, author_id.
6.0 Service Architecture & API
6.1 TicketService

Responsibility: Manages all business logic related to support tickets.
Key Methods:
createTicket(data): Creates a new ticket and its initial reply. Dispatches a TicketCreated notification.
addReply(ticket, user, body, type): Adds a new reply to a ticket. If the reply is public, it dispatches a TicketUpdated notification to the customer.
changeStatus(ticket, newStatus): Manages the state transitions of a ticket.
assignTicket(ticket, user, team): Handles assignment logic. Dispatches a notification to the newly assigned agent/team.
createWorkOrderFromTicket(ticket): An integration method that gathers context from the ticket and calls WorkOrderService->createRepairOrder, linking the new work order back to the source ticket.
6.2 Email Ingestion

Mechanism: A dedicated mailbox (e.g., support@skyfinetworks.com) will be configured to forward incoming emails to a script or a webhook provided by a service like SendGrid (the "Inbound Parse Webhook").
Logic:
The webhook receives a JSON payload representing the email.
The system parses the From: address to find an existing customer record.
It parses the Subject line. If it contains a ticket UID ([TKT-...]), the email content is added as a reply to the existing ticket.
If no UID is found, a new ticket is created using the subject and body of the email.
If the sender's email is not found in the system, a ticket can be created in an "unassigned" state, or a bounce-back email can be sent.
6.3 API Endpoints

GET /api/v1/tickets: Returns a paginated list of tickets, with powerful filtering (by status, assignee, priority).
POST /api/v1/tickets: Creates a new ticket.
GET /api/v1/tickets/{id}: Retrieves a single ticket and all its public and internal replies (for staff).
POST /api/v1/tickets/{id}/replies: Adds a new reply to a ticket.
PATCH /api/v1/tickets/{id}: Updates a ticket's properties (status, assignee, priority).
7.0 User Interface
7.1 Staff-Facing UI

Ticket Queue View (/tickets):
The main workspace for the support team. A powerful data table showing all tickets.
Pre-defined, clickable views: "My Open Tickets," "Unassigned Tickets," "Recently Closed."
Advanced filtering and sorting capabilities are essential.
Ticket Detail View (/tickets/{id}):
A two-column layout is common and effective:
Left Column (Context): Displays the full Customer 360° View in a condensed format. The support agent can immediately see the customer's status, active services, recent invoices, and network status without leaving the page. This is the system's key advantage.
Right Column (Timeline): A chronological feed of all ticket_replies. A form at the top allows the agent to write a new reply, with a toggle for "Public Reply" vs. "Internal Note."
Header: Shows the ticket subject, status, priority, and assignee. The agent can change these properties directly from the header.
Actions: Buttons for "Create Work Order," "Link to KB Article," etc.
7.2 Customer Portal UI

My Tickets (/portal/support): A simple list view showing the customer's own open and closed tickets.
Ticket Detail View (/portal/support/{id}):
Shows the ticket subject and status.
Displays a timeline of public replies only.
Provides a form for the customer to add a new reply.
8.0 Integration Points
Customer Management: The ticket system is fundamentally linked to the customer entity. The UI heavily leverages this to provide context.
Notifications: The TicketService is a major dispatcher of notifications (TicketCreated, TicketUpdated, TicketAssigned).
Work Orders (Operations): The ability to escalate a ticket into a field service Work Order is a critical workflow for resolving physical issues.
Network Module: The ticket detail view will pull in real-time diagnostic data from the NMS module to help the agent troubleshoot connectivity issues.
9.0 Risks
Risk	Description	Mitigation Strategy
Lack of Context	A support agent has to switch between multiple screens (billing, network status, CRM) to understand a customer's problem.	The architecture explicitly mitigates this by designing the Ticket Detail View to include the Customer 360° context directly on the page. The backend APIs must be designed to provide this contextual data efficiently.
Email Loop	An automated email (e.g., an out-of-office reply) responds to our ticket update email, which then creates another reply, triggering another email, and so on.	The email ingestion logic must be smart. It should parse headers to detect auto-replies (Precedence: bulk, X-Auto-Response-Suppress) and ignore them. It should also implement rate-limiting per sender to prevent a single email address from creating too many tickets/replies in a short period.
SLA Management	The system does not help managers track if tickets are being responded to and resolved in a timely manner.	This is a v2 feature, but the architecture should support it. We can add first_response_at and resolved_at timestamps to the support_tickets table. A separate SlaService can then calculate metrics based on ticket priority and pre-defined Service Level Agreement (SLA) policies.
Poor User Experience for Agents	The ticketing UI is slow or requires too many clicks to perform common actions.	The UI/UX must be designed with agent efficiency as the top priority. Use keyboard shortcuts, pre-canned "macro" responses for common issues, and ensure the UI is extremely fast and responsive.