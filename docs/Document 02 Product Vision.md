Document 02: Product Vision
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
The purpose of this document is to define the product vision for the SkyFi Networks ISP Management System.

This vision serves as the North Star for all stakeholders - from executive leadership and product owners, to architects, developers, QA, and operations teams. It clearly articulates:

Why we are building this system
What business outcomes we expect to achieve
Who we are building it for
What success looks like
The long-term strategic value it provides to SkyFi Networks
This document is not a list of features. It is a declaration of the problem we are solving, the value we are delivering, and the future state we are working towards.

2.0 Responsibilities
Role	Responsibility
Executive Leadership	Approve and champion the product vision. Ensure all business decisions align with this vision.
Product Owner	Own, maintain, and communicate the vision. Prioritize the product backlog against the vision.
Principal Architect	Ensure the technical architecture enables and supports the product vision. Validate all architectural decisions against it.
Department Heads	Validate that their department's needs are accurately represented in the vision and desired outcomes.
Development Leads	Ensure feature development and implementation choices remain aligned with the long-term vision.
QA & Operations	Validate that the delivered product meets the intended customer and business outcomes outlined in this vision.
3.0 Vision Statement
"To be the single source of truth that powers SkyFi Networks - unifying our customers, network, and finances into one intelligent, scalable, and profitable ISP operating system."

We envision a future where:

A prospect becomes a paying subscriber with minimal human intervention
Our staff have complete visibility into every customer, every invoice, every ticket, and every network device - all from a single platform.
Our field teams are empowered with the data they need to install, troubleshoot, and resolve issues faster than ever before.
Our finance team has complete, auditable control of our revenue stream in real-time
Our network engineers can proactively monitor, manage, and scale our wireless infrastructure with confidence
Our customers have complete control of their services through an intuitive, modern self-service portal
This system is the operational backbone that allows SkyFi Networks to scale from a regional WISP, to a large, multi-region enterprise ISP.

4.0 Mission Statement
Our mission is to eliminate operational silos, automate revenue workflows, and empower both our employees and customers by consolidating all ISP operations into one unified, enterprise-grade platform.

We will achieve this by building an integrated ERP + CRM + Billing + Finance + Network Management System that is reliable, secure, scalable, and built specifically for the needs of a Wireless Internet Service Provider.

5.0 Problem Statement
SkyFi Networks, like most growing WISPs, faces significant operational challenges due to a reliance on disconnected tools and manual processes.

Problem Area	Current State	Business Impact
Data Silos	CRM, billing, accounting, inventory, and network monitoring are all in different systems (or spreadsheets)	No single source of truth. Data duplication, inconsistencies and wasted time.
Quote-to-Cash Inefficiency	Sales, installation, provisioning, and billing are completely disconnected manual workflows.	Long lead-to-activation times. High administrative costs. Lost revenue opportunities.
Billing Complexity	Proration, usage-based billing, suspensions, dunning, and plan changes are handled manually.	Increased billing errors, late payments, revenue leakage, and poor customer experience.
Provisioning Bottleneck	Technicians must manually configure services on MikroTik devices after installation.	Slow service activation, human error, and poor scalability.
Lack of Visibility	Management has no real-time view of revenue, churn, network health, inventory, or technician productivity.	Slow, reactive decision making. Inability to scale efficiently.
Poor Customer Experience	Customers cannot view invoices, pay bills, check service status, or open support tickets in one place.	Higher support call volume, increased churn, and damaged brand reputation.
Inventory & Purchasing	Equipment, CPE, cables, and tower assets are tracked in spreadsheets.	Stockouts, over-ordering, lost assets, and inaccurate cost tracking.
Field Operations	Installation teams lack visibility into jobs, customer info, and required equipment.	Missed appointments, inefficient routing, increased truck rolls, and longer MTTR.
6.0 Target Users & Personas
The system must serve the diverse needs of our internal teams and our customers.

6.1 Internal Users
User	Primary Goal	Key Pain Points
Super Administrator	Full system oversight, security, and platform control	Needs granular control over all system functions across all regions.
Company Owner	Monitor profitability, growth, and company health	Needs high-level dashboards, revenue reporting, and churn metrics.
Regional Manager	Manage staff, customers, and profitability by region	Needs regional reporting, tower visibility, and team performance metrics.
Sales Team	Convert leads into paying customers	Needs lead tracking, quoting, service availability, and seamless customer creation.
Installation Team	Complete installations efficiently	Needs work orders, customer location data, site surveys, and equipment requirements.
Field Technicians	Troubleshoot and repair customer issues	Needs service history, network status, device info, and ticket management on the go.
Network Engineers	Monitor, maintain and scale the wireless network	Needs real-time device monitoring, PPPoE/Hotspot visibility, tower health, and MikroTik integration.
Finance Department	Manage billing, invoicing, payments, accounts receivable, and accounting	Needs automated billing, proration, dunning, payment reconciliation, and financial reporting.
Customer Support	Resolve customer issues quickly	Needs 360-degree customer view, ticketing, payment history, and service status.
Inventory Manager	Track equipment, stock, purchasing and vendors	Needs real-time inventory, purchase orders, vendor management, and asset tracking.
6.2 External Users
User	Primary Goal	Key Needs
Customers	Manage their account and services	Self-service portal for viewing invoices, making payments, checking usage, opening tickets, and updating account information.
7.0 Goals & Strategic Objectives
7.1 Business Goals
Goal	Objective	Success Metric (KPI)	Target
Revenue Optimization	Reduce revenue leakage and automate quote-to-cash	Revenue Leakage Rate	< 1%
Scalability	Support rapid regional growth without linear increase in headcount	Customers per Admin Employee	500% increase over legacy process
Profitability	Reduce cost to acquire and cost to serve customers	Cost to Serve (per subscriber/month)	25-40% reduction
Churn Reduction	Improve customer experience and reduce preventable churn	Monthly Churn Rate	15-30% reduction within 12 months
Operational Efficiency	Automate manual processes across departments	Manual Task Time	60% reduction in time spent on billing, provisioning, and support admin
Faster Time-to-Activation	Reduce time from sale to active service	Average Activation Time (Sale to Active)	< 24 hours (for standard installations)
Improved Cash Flow	Increase on-time payments	Days Sales Outstanding (DSO)	30-50% reduction
7.2 Product Goals
These are the tangible product outcomes that enable the business goals:

Product Goal	Description
Unified Platform	Create one system where staff can complete 90% of their daily tasks without switching between multiple tools.
360-Degree Customer View	Any authorized user must be able to see a customer's complete profile: contact info, services, invoices, payments, tickets, network status, and installation history in a single view.
End-to-End Automation	Automate the entire lifecycle: Lead -> Quote -> Contract -> Installation -> Provisioning -> Billing -> Collection -> Support
Intelligent Provisioning	Automatically provision services on MikroTik devices via API when a customer is activated (PPPoE, Hotspot, Queue Management, Address Lists).
Real-Time Visibility	Provide real-time dashboards for revenue, AR, network health, ticket volume, and technician performance.
Self-Service First	Empower customers to resolve common issues (payments, invoices, basic support) without contacting support.
Enterprise Grade Security	Ensure complete data integrity, auditability, and security across all modules.
8.0 Architecture (Vision Level)
While Document 05 covers detailed architecture, the product vision dictates specific architectural principles:

Principle	Vision	Rationale
Unified Data Model	Single source of truth for Customers, Billing, Finance, Network and Inventory.	Eliminates data silos. Critical for scaling a multi-region ISP.
Modular Monolith	Logical separation of modules (CRM, Billing, Finance, NMS, Inventory, Support), while remaining a single deployable backend.	Balances speed of development with enterprise maintainability. Allows multiple teams to work in parallel. Clear path to microservices if needed.
API-First	Every piece of functionality must be exposed via a well documented REST API.	Enables the React SPA, future mobile apps (Technician App + Customer App), and third-party integrations.
Real-Time Ready	System must support near real-time updates for network monitoring, ticket updates, and billing events.	Critical for Network Engineers and Support teams. Long-term enables WebSocket based notifications.
Extensibility	Architecture must allow easy integration with new vendors (Ubiquiti, Cambium, Mimosa, etc.) beyond MikroTik.	Future expansion is a core business requirement.
Security by Design	RBAC, Audit Logging, Encryption, and secure API design must be baked into the foundation.	Financial data + Customer data + Network control requires enterprise security posture.
9.0 Workflow (End-to-End Vision)
This is the "North Star" workflow that the entire system is built around. This unified quote-to-cash workflow represents our ideal state.

mermaid

flowchart LR
    subgraph "Sales & Marketing"
        A[Lead Created] --> B[Qualify Lead]
        B --> C[Check Service Availability]
        C --> D[Create Quote]
        D --> E[Customer Accepts Quote]
        E --> F[Create Contract & Account]
    end

    subgraph "Operations"
        F --> G[Create Installation Work Order]
        G --> H[Schedule Installation]
        H --> I[Site Survey]
        I --> J[Install Equipment]
    end

    subgraph "Network Provisioning"
        J --> K[Validate Signal]
        K --> L[Auto-Provision Service on MikroTik]
        L --> M[Service Activated]
    end
    
    subgraph "Billing & Finance"
        F -- Creates Pending Customer --> N[Create Initial Invoice]
        M -- Triggers Billing Cycle --> O[Generate Recurring Invoice]
        O --> P[Send Invoice]
        P --> Q[Customer Makes Payment]
        Q --> R[Payment Gateway Reconciliation]
        R --> S[Account Updated/Un-suspended]
    end
    
    subgraph "Support & Retention"
        T[Customer Opens Ticket] --> U[Assign to Technician]
        U --> V[Troubleshoot/Resolve]
        V --> W[Close Ticket]
        Q -- Positive Event --> X[Increased Retention]
    end

    M -- Customer Live --> T
    style M fill:#90EE90
    style Q fill:#90EE90
Vision Outcome: This entire workflow should be automated wherever possible. A single action in one module should seamlessly trigger the next step in downstream modules with zero manual data re-entry.

10.0 Dependencies
The product vision is dependent on the following:

Dependency	Type	Impact
Executive Buy-In	Internal	Critical. This is a large, enterprise-wide transformation. Requires full organizational commitment.
Cross-Departmental Cooperation	Internal	Finance, Support, Sales, Network Engineering, and Field Ops must actively participate in requirements gathering and UAT.
MikroTik RouterOS API Access	External/Technical	Core to our provisioning vision. We require stable, programmatic access to RouterOS API on all production routers.
Payment Gateway Partnership	External	Must select a secure, reliable, PCI compliant payment gateway (e.g. Stripe). This is critical for automated billing.
Stable Network Infrastructure Data	Internal	Accurate tower data, service availability mapping, and network topology data is required for quoting and provisioning.
Data Migration Readiness	Internal	Clean, validated data from legacy billing, CRM, and accounting systems must be available for migration.
Skilled Development Resources	Internal	Team must have strong expertise in React/TypeScript, PHP, MySQL, and MikroTik API integration.
11.0 Business Rules & Validation Rules (Vision)
The vision requires a consistent, enforceable set of business rules across the platform.

11.1 Core Business Rules
Area	Business Rule	Rationale
Service Availability	A quote cannot be created for a service if the address/tower has no capacity.	Prevents over-selling bandwidth/tower capacity.
Contract Creation	A customer cannot be activated without an accepted quote, contract, and installation work order.	Ensures proper legal and operational process.
Provisioning	Service cannot be provisioned on MikroTik unless installation is marked "Complete" and "Passed Quality Check".	Prevents accidental provisioning of non-existent installations.
Billing	Invoices must be generated automatically on the customer's billing cycle date. Proration is mandatory for mid-cycle plan changes, suspensions, or activations.	Accurate revenue recognition and customer fairness.
Suspension	Customers with an overdue balance past their dunning period must be automatically suspended from service.	Protects revenue and reduces bad debt.
Reactivation	Service cannot be unsuspended until the outstanding balance (including reconnection fees) has been paid in full.	Enforces collections policy.
Inventory	Installation work order cannot be marked "Ready for Install" if required equipment is not available in the assigned warehouse/stock.	Prevents costly truck rolls and installation failures.
Permissions	No user can perform an action they do not have explicit permission for. All financial actions require appropriate role-based permissions.	Security, compliance, and segregation of duties.
11.2 Validation Rules (Vision)
Frontend Validation: Provide immediate, user-friendly feedback using Zod schemas. This is critical for staff productivity.
Backend Validation: All data must be validated at the API level. Frontend validation is for UX, backend validation is for security and data integrity.
Shared Validation: Schemas must be shared between frontend and backend where possible to ensure consistency and reduce duplication.
Financial Validation: All monetary values must be validated for currency, precision (2 decimal places), and valid ranges.
Audit Requirement: No destructive operation (delete) should permanently delete critical data. Soft-deletes must be used for Customers, Invoices, Payments, and Assets.
12.0 Security & Permissions
Security is non-negotiable. Our vision requires enterprise-grade security.

12.1 Security Principles
Principle	Vision
Zero Trust	Never trust the client. Every API request must be authenticated and authorized.
Least Privilege	Users should only have the minimum permissions required to perform their job function.
Defense in Depth	Security controls at every layer (Network, Application, API, Database, Authorization).
Secure by Default	New features must be secure by default. Public endpoints must be explicitly defined.
Complete Auditability	Every sensitive action (Create, Read, Update, Delete, Financial Transaction, Role Change, Network Provisioning) must be logged.
12.2 Permission Vision
The RBAC system must support:

Granular Permissions: Ability to grant/revoke specific actions (e.g., "View Invoice", "Create Payment", "Provision PPPoE", "Delete Ticket").
Role-Based Assignment: Users belong to one or more roles (e.g., Finance Clerk, Support Agent, Network Engineer).
Regional Segmentation: Regional Managers must only have access to data for their assigned regions (Customers, Towers, Revenue).
Data-Level Security: Certain data (e.g., full credit card numbers) must never be stored. Other data must be masked based on role.
Segregation of Duties: Critical functions (e.g., Create Invoice, Approve Refund, Delete Payment) must require different roles to prevent fraud.
13.0 Future Expansion
This system is built for the future of SkyFi Networks. Our vision extends well beyond the initial release.

Expansion Area	Vision	Timeline
Multi-Vendor Support	Expand beyond MikroTik to support Ubiquiti UniFi, UISP, Cambium Networks, Mimosa, and other WISP hardware vendors.	Post-Launch (Phase 3+)
Mobile Applications	Native iOS/Android applications for Field Technicians and Customers, powered by the same REST API.	Phase 3+
VoIP Integration	Extend platform to manage VoIP services, DIDs, call billing, and SIP trunking.	Future Roadmap
Fiber ISP Support	Extend billing and provisioning models to support Fiber to the Home (FTTH) services (GPON/ONT provisioning concepts).	Long-Term
Usage-Based Billing	Advanced bandwidth quota tracking, burst billing, and overage charges for different service tiers.	Phase 2+
Network Automation	Automated configuration backups, firmware management, and bulk provisioning across the entire network fleet.	Long-Term
AI & Predictive Analytics	Predict churn, forecast tower capacity, detect network anomalies, and optimize technician routing using machine learning.	Long-Term
Multi-Tenant Capabilities	Ability for SkyFi Networks to manage sub-ISPs/resellers on the same platform with isolated data.	Long-Term Strategic Goal
14.0 Best Practices & Enterprise Recommendations
As the Principal Enterprise Architect, the following best practices must guide the product:

Category	Recommendation
Customer-Centric	Every feature must be evaluated against "Does this improve the customer experience or reduce their effort?"
Automation First	If a task is done more than 10 times per week by staff, it should be automated. Manual processes are technical debt.
API-First Development	Build the API first, then the frontend. This ensures a stable contract and enables parallel development.
Shared Contracts	Use Zod schemas (frontend) as the source of truth for validation. Backend must enforce the same business rules.
Data Integrity	Use foreign keys, constraints, transactions, and soft-deletes. Never sacrifice data integrity for convenience.
Observability	Build for monitoring, logging, and tracing from day one. You cannot manage what you cannot see.
Iterative Delivery	Deliver value incrementally. Do not attempt to build everything at once. MVP first (Phase 1), then expand.
Platform Thinking	Build this as a platform, not a product. The REST API is just as important as the UI. This unlocks future mobile apps and 3rd party integrations.
Industry Standards	Follow patterns from industry leaders (Salesforce for CRM, Stripe for Billing, Cisco Prime for NMS concepts). Don't reinvent the wheel.
15.0 Diagrams
15.1 Value Stream Diagram
This diagram illustrates the value delivered to the customer and the business:

mermaid

flowchart LR
    subgraph "Value Stream: Quote-to-Cash"
        Lead[Lead] --"Sales Effort"--> Quote[Quote]
        Quote --"Automated Availability Check"--> Contract[Contract]
        Contract --"Automated Work Order"--> Install[Installation]
        Install --"Automated Provisioning"--> Active[Active Service]
        Active --"Automated Billing"--> Invoice[Invoice Generated]
        Invoice --"Self-Service Payment"--> Payment[Payment Received]
        Payment --"Automated Reconciliation"--> Revenue[Recognized Revenue]
    end
    
    style Active fill:#90EE90
    style Revenue fill:#90EE90
    
    Lead -- "Value: New Customer" --> Revenue
15.2 Capability Map
High-level business capabilities the system must provide:

mermaid

mindmap
  root((SkyFi Networks Platform))
    Customer Management
      Lead Management
      Account Management
      Service Management
      Self Service Portal
    
    Sales & CRM
      Quoting
      Service Availability
      Contract Management
    
    Operations
      Installation Management
      Work Orders
      Field Technician Tools
      Site Surveys
    
    Network Management
      Tower Management
      Infrastructure Tracking
      PPPoE Management
      Hotspot Management
      MikroTik Integration
      Monitoring
    
    Billing & Finance
      Plan Management
      Invoicing
      Proration
      Payments
      Dunning
      Refunds
      General Ledger
      Accounts Receivable
    
    Inventory & Procurement
      Asset Management
      Stock Management
      Purchase Orders
      Vendor Management
    
    Support & Reporting
      Ticketing System
      Knowledge Base
      Reporting
      Analytics
      Audit Logging
16.0 Examples
Example 1: The Ideal Customer Journey
Scenario: A new customer finds SkyFi Networks and signs up for service.

Stage	Action	System Response (Vision)
Discovery	Customer enters address on website	System instantly checks tower coverage and available capacity. Returns available plans.
Quote	Customer selects plan	System automatically generates a quote with installation fees, monthly cost, and expected speeds.
Conversion	Customer accepts quote and enters details	System creates customer account, contract, and automatically generates installation work order. Sales rep notified.
Scheduling	Installation team schedules appointment	System shows technician availability, optimal route, and required equipment. Customer receives automated email/SMS.
Installation	Technician completes install	Technician marks work order "Complete" from their interface. System automatically runs validation checks.
Activation	System provisions service	Upon successful installation, system automatically creates PPPoE profile, IP assignment, and bandwidth queues on the appropriate MikroTik router. Service is live.
Billing	First invoice generated	System automatically generates prorated first invoice and sets up recurring billing cycle. Invoice is emailed to customer.
Payment	Customer pays via portal	Customer logs into portal, pays with saved payment method. Payment Gateway processes payment, system reconciles it and marks invoice as paid.
This entire process takes < 24 hours from quote acceptance to active service, with zero duplicate data entry.

Example 2: Proactive Network Management
Scenario: A tower is approaching capacity.

Network Engineer sees real-time dashboard showing tower utilization at 92%.
System automatically flags this tower as "Near Capacity".
Sales team is prevented from selling new services on this tower (Service Availability check fails).
Regional Manager receives an alert and can prioritize network upgrade.
This prevents overselling, poor customer experience, and costly emergency upgrades.
17.0 Risks
These are the strategic/product risks that could prevent us from achieving this vision:

Risk	Probability	Impact	Mitigation Strategy
Over-Engineering	Medium	High	Strict adherence to MVP approach. Build Phase 1 features first. Do not build "nice-to-haves" until core value is delivered.
Lack of Cross-Department Adoption	Medium	Critical	Executive sponsorship, department head buy-in, and extensive user training. Involve end-users throughout the entire development lifecycle (Agile).
Underestimating MikroTik Complexity	High	Critical	Prototype MikroTik API integration immediately. Build a proof-of-concept before full development. Account for RouterOS version differences across fleet.
Scope Creep	High	High	Product Owner must fiercely protect the vision. All feature requests must be mapped to business goals/KPIs defined in this document.
Data Migration Failure	Medium	High	Dedicate significant time to data audit, cleansing, mapping, and multiple dry-runs in staging. Build robust migration and validation tools.
Integration Fragility	Medium	Medium	Payment Gateway, Email/SMS, and Mapping services must be abstracted behind service interfaces. Implement robust retry logic, circuit breakers, and comprehensive error handling.
Unrealistic Timeline Expectations	High	Medium	This is enterprise software. Communicate realistic phased approach to leadership. Deliver incremental value, not everything at once.
Change Resistance	Medium	Medium	Provide comprehensive training, excellent UX, and clear communication on "What's in it for me" for each department. Run parallel with legacy systems during transition period.
18.0 Implementation Notes
Achieving this vision requires a phased, value-driven approach:

Phase 1 (MVP - Foundation)
Goal: Deliver immediate, high-value functionality. Focus on Quote-to-Cash foundation.

Core CRM (Customers, Leads, Contacts)
Service Plans & Service Availability
Quoting & Contract Management
Basic Billing (Recurring Invoices, Payments, Basic Proration)
Customer Self-Service Portal (View Invoices, Make Payments, Open Tickets)
Basic Support Ticketing
Core RBAC & Authentication
Success Criteria: Ability to sell, bill, and collect payment from a new customer. This alone provides massive ROI.

Phase 2 (Operations & Network Core)
Goal: Automate operations and provide network visibility.

Installation Workflow (Work Orders, Scheduling, Site Surveys)
Inventory Management (Stock Tracking, Basic Assets)
MikroTik Integration (Read-only monitoring + PPPoE Account Management)
Hotspot Management
Tower Management & Infrastructure Tracking
Dunning Process (Automated Suspensions/Reconnections)
Advanced Reporting (Revenue, AR, Churn)
Success Criteria: Complete automation of installation-to-provisioning workflow. Significant reduction in truck rolls and activation time.

Phase 3 (Enterprise Finance & Advanced NMS)
Goal: Full enterprise ERP capabilities.

Complete Finance Module (Chart of Accounts, General Ledger, Accounts Receivable/Payable concepts)
Purchasing & Vendor Management (Purchase Orders, Receiving, Vendor Bills)
Advanced Network Management (Queue Management, Bandwidth Control, Bulk Provisioning, Configuration Auditing)
Multi-Region Support & Data Segmentation
Advanced Analytics Dashboard
Complete Audit Logging & Compliance Features
Mobile App Foundations (Technician App API Readiness)
Success Criteria: Complete replacement of legacy accounting tools. Platform is scalable across multiple regions.

Long Term (Phase 4+)
Goal: Platform expansion & Intelligence

Multi-Vendor Hardware Support (Ubiquiti, Cambium, etc.)
Native Mobile Applications
Predictive Analytics & AI
VoIP Services
Reseller/Multi-Tenant Functionality
19.0 Success Criteria Summary
The product vision will be considered successfully achieved when:

Metric	Target State
Single Source of Truth	100% of customer, billing, and network data exists in SkyFi Networks Platform. No critical data in spreadsheets.
Activation Time	90% of standard installations activated within 24 hours of quote acceptance.
Automation Rate	95% of recurring invoices, 90% of PPPoE provisions, and 85% of suspension/reconnection actions are fully automated.
Staff Efficiency	70% reduction in time spent on cross-departmental administrative tasks.
Customer Self-Service	60% of invoice payments and 40% of support tickets are handled entirely through the customer portal (without agent interaction).
Data Visibility	Executives have access to real-time revenue, churn, AR, and network health dashboards with < 5 minute data latency.
Scalability	System can support 50,000+ subscribers, 500+ network devices, and 25+ concurrent regions with acceptable performance targets (as defined in Non-Functional Requirements).
This concludes Document 02: Product Vision.

This vision will serve as the guiding principle for all subsequent documentation, architectural decisions, and development efforts. Every feature, module, and technical decision must trace back to the goals and outcomes defined within this document.
