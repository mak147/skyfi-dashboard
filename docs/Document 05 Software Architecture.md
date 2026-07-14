Document 05: Software Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document defines the high-level software architecture for the SkyFi Networks ISP Management System. It outlines the primary architectural style, the major components and their interactions, the core design principles that govern development, and the key technology choices that enable the system's functional and non-functional requirements.

The purpose is to provide a clear and coherent architectural blueprint for the development teams, ensuring that the system is built in a structured, consistent, and maintainable manner. It serves as the primary technical guide for constructing the software's internal structure.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define, own, and maintain this architectural document. Provide guidance and clarification to the development teams.
Development Lead	Enforce adherence to the defined architecture during code reviews and sprint planning. Champion the architectural principles within the team.
Senior Developers	Implement features according to the architectural patterns and principles. Provide feedback on the architecture's practicality.
All Developers	Understand and follow the architectural guidelines when writing code.
3.0 Goals
The goals of this specific software architecture are to:

Promote Developer Velocity: Enable multiple developers to work on different parts of the system concurrently with minimal friction.
Ensure Maintainability: Create a system that is easy to understand, debug, and enhance over its lifetime, reducing total cost of ownership.
Fulfill NFRs: Directly address the non-functional requirements for performance, scalability, security, and reliability.
Decouple Presentation from Logic: Establish a clean separation between the user interface (Frontend) and the business logic/data (Backend).
Provide a Path for Evolution: Design a system that can evolve and scale with the business, including the potential for future refactoring into microservices if necessary.
4.0 Architectural Style: 3-Tier Modular Monolith with SPA
The SkyFi Networks platform will be implemented using a 3-Tier Architecture consisting of a Presentation Tier, an Application Tier, and a Data Tier. The Application Tier will be built as a Modular Monolith.

3-Tier Architecture: This is a classic, well-understood pattern that separates concerns into logical layers.

Presentation Tier: The user interface, responsible for displaying data and capturing user input. This will be a React Single Page Application (SPA).
Application Tier: The "backend," responsible for all business logic, data processing, and external service integrations. This will be a PHP-based REST API.
Data Tier: The persistence layer, responsible for storing and retrieving data. This will be a MySQL database.
Modular Monolith: While the backend is a single deployable unit (a monolith), its internal structure is organized into distinct, loosely-coupled modules that correspond to business capabilities (e.g., Billing, CRM, NMS). This approach offers the deployment simplicity of a monolith while providing the organizational benefits of a more service-oriented design.

Architectural Justification: This style was chosen as a pragmatic balance. A full microservices architecture would introduce significant operational overhead and distributed systems complexity not justified at this stage. The Modular Monolith provides a clear path for future evolution: a well-defined module can be extracted into its own microservice if it becomes a performance bottleneck or requires independent scaling.

5.0 High-Level System Architecture Diagram
This diagram illustrates the primary components and their interactions at a high level.

mermaid

graph TD
    subgraph "User's Browser"
        ReactSPA[React SPA Frontend]
    end

    subgraph "Cloud Infrastructure (AWS/Cloudflare)"
        LB[Load Balancer / CDN]
        subgraph "Application Tier (Auto-Scaling Group)"
            PHP1[PHP App Server 1]
            PHP2[PHP App Server 2]
            PHP3[PHP App Server n...]
        end
        DB[(MySQL Database - RDS)]
        Cache[(Redis Cache)]
    end

    subgraph "External Services"
        PG[Payment Gateway<br>(Stripe)]
        NS[Notification Service<br>(SendGrid/Twilio)]
        MS[Mapping Service<br>(Mapbox)]
    end

    subgraph "SkyFi Network"
        MKT[MikroTik Routers]
    end

    User[User (Staff/Customer)] --> ReactSPA
    ReactSPA -- HTTPS --> LB
    LB -- Distributes Traffic --> PHP1
    LB -- Distributes Traffic --> PHP2
    LB -- Distributes Traffic --> PHP3

    PHP1 -- REST API Calls --> LB
    PHP2 -- REST API Calls --> LB
    PHP3 -- REST API Calls --> LB

    PHP1 --- DB
    PHP2 --- DB
    PHP3 --- DB

    PHP1 --- Cache
    PHP2 --- Cache
    PHP3 --- Cache

    PHP1 -- API --> PG
    PHP2 -- API --> NS
    PHP2 -- API --> MS
    PHP3 -- RouterOS API --> MKT

    style User fill:#cde4ff
    style MKT fill:#ffc2c2
6.0 Tiered Architecture Breakdown
6.1 Presentation Tier: React Single Page Application (SPA)
Technology: React, TypeScript, Vite, Tailwind CSS.
Responsibilities:
Render the entire user interface in the client's browser.
Manage UI state (e.g., open modals, form inputs).
Handle user interactions and routing (via React Router).
Communicate with the Application Tier exclusively via the REST API.
Fetch, cache, and synchronize server state (via TanStack Query).
Perform client-side validation for immediate user feedback (via Zod).
Architectural Principles:
Dumb Component, Smart API: The React frontend is considered a consumer of the API. It should contain minimal to no business logic. All critical business rules must be enforced by the backend.
Component-Based: The UI will be constructed from a library of reusable, self-contained components as defined in the Component Library Specification.
6.2 Application Tier: PHP Modular Monolith
Technology: PHP, REST API, JWT Authentication.
Responsibilities:
Implement all business logic and workflows (e.g., billing cycle, dunning process).
Expose all functionality through a stateless RESTful API.
Enforce all data validation and business rules. This is the authoritative source of truth for validation.
Handle user Authentication (verifying identity) and Authorization (checking permissions via RBAC).
Interact with the Data Tier (MySQL) for all data persistence.
Integrate with all external services (Payment Gateway, MikroTik routers, etc.).
Internal Module Structure: The monolith's codebase will be organized into namespaces/directories corresponding to business capabilities. Modules should only communicate with each other through well-defined service interfaces, not by directly accessing each other's database tables or internal classes.
mermaid

graph TD
    subgraph "PHP Application Tier (Modular Monolith)"
        API[API Gateway/Router Layer]
        
        subgraph "Core Services"
            Auth[Auth Service<br>(JWT, RBAC)]
            Notify[Notification Service<br>(Email, SMS Adapters)]
            Log[Logging Service]
        end

        subgraph "Business Modules"
            CRM[CRM Module<br>(Customers, Leads)]
            BIL[Billing Module<br>(Invoices, Plans, Payments)]
            NMS[NMS Module<br>(MikroTik Adapter, Provisioning)]
            SUP[Support Module<br>(Tickets)]
            INV[Inventory Module<br>(Assets, Stock)]
        end
        
        API --> Auth
        API --> CRM
        API --> BIL
        API --> NMS
        API --> SUP
        API --> INV

        CRM -- Calls --> Notify
        BIL -- Calls --> Notify
        BIL -- Calls --> NMS
        SUP -- Calls --> Notify
    end

    style API fill:#b4e8c8
Statelessness: This is a critical principle driven by NFR-SCAL-003. The PHP application must not store any user session state on the local server (e.g., in $_SESSION). All state required to process a request must be contained within the request itself (e.g., the JWT) or retrieved from the database/cache. This allows any server in the cluster to handle any user's request, enabling seamless horizontal scaling.
6.3 Data Tier: MySQL Database
Technology: MySQL.
Responsibilities:
Persist all application data.
Enforce data integrity through schemas, foreign keys, constraints, and transactions.
Provide reliable, transactional (ACID-compliant) storage for critical financial and customer data.
Execute queries efficiently.
Architectural Principles:
API-Only Access: The database must only be accessible from the Application Tier. Direct connections from the frontend or any other service are strictly forbidden.
Schema as Contract: The database schema is the ultimate source of truth for data structures. All schema changes must be managed through a formal migration process.
7.0 Communication Patterns
From	To	Pattern	Protocol	Payload	Authentication
React SPA	PHP Backend	Synchronous Request/Response	HTTPS	JSON	Bearer Token (JWT)
PHP Backend	MySQL DB	Connection Pool	TCP/IP	SQL	DB Credentials
PHP Backend	Redis Cache	Request/Response	TCP/IP	Redis Protocol	(Varies by setup)
PHP Backend	External Services	API Call (Adapter Pattern)	HTTPS (REST) or specific	JSON / XML	API Keys / OAuth
PHP Backend	MikroTik Router	API Call (Adapter Pattern)	RouterOS API	Proprietary	API user/pass
Enterprise Recommendation: The Adapter Pattern for external services is crucial. We will create an interface PaymentGateway and an implementation StripeAdapter. All billing code will use the interface. This allows us to switch to a different payment gateway in the future by simply creating a new adapter (e.g., BraintreeAdapter) with minimal changes to the core business logic.

8.0 Architectural Decisions & Justifications
Decision	Justification	Addressed NFRs
Modular Monolith	Balances development speed and deployment simplicity with maintainability. Avoids premature optimization and complexity of microservices.	NFR-MAIN-001
Stateless Backend	Essential for horizontal scalability and high availability. Simplifies deployment and load balancing.	NFR-SCAL-003, NFR-REL-001
React SPA	Provides a rich, responsive, modern user experience akin to leading SaaS platforms. Decouples frontend and backend development lifecycles.	NFR-USA-001, NFR-PERF-003
REST API with JWT	A mature, well-understood, and stateless approach for client-server communication. JWT is a standard for token-based authentication in SPAs.	NFR-SEC-001, NFR-SEC-005
API-First Principle	Enforces a clean separation of concerns and a stable contract. Enables parallel development and future clients (e.g., mobile apps).	NFR-MAIN-004
9.0 Future Expansion
This architecture is designed for evolution:

Module to Microservice: If the Billing module becomes a performance bottleneck or requires a different technology stack (e.g., for high-throughput transaction processing), its well-defined boundaries and interface-based communication allow it to be extracted into a separate microservice. The BillingAdapter within the monolith would be updated to call the new microservice's API instead of the local class.
New Network Vendors: The NMS module's use of an adapter pattern (MikroTikAdapter) means supporting a new vendor like Ubiquiti simply involves creating a UbiquitiAdapter that implements the same NetworkDevice interface, leaving the core provisioning logic untouched.
Mobile Applications: The API-first approach means a native mobile application can be developed as another client for the existing REST API with no changes to the backend architecture.
10.0 Security
All traffic is encrypted via HTTPS (NFR-SEC-001).
The Application Tier acts as a gatekeeper, enforcing authentication (FR-SYS-001) and authorization (FR-SYS-003) on every API request.
The database is in a private network, inaccessible from the public internet.
The principle of least privilege is enforced by the RBAC system within the Auth Service middleware.
11.0 Risks
Risk	Description	Mitigation Strategy
Monolith Coupling	Without discipline, modules within the monolith become tightly coupled, turning it into a "big ball of mud."	Strict code reviews to enforce module boundaries. Use of Dependency Injection to manage dependencies. Adherence to interface-based communication between modules.
Database as a Bottleneck	As data volume grows, the single database can become a performance bottleneck.	Proactive database performance monitoring. A robust indexing strategy. Use of read replicas for reporting workloads. Caching layer (Redis) for high-read, low-write data.
API Versioning	As the application evolves, breaking changes to the API can disrupt the frontend or other clients.	Implement an API versioning strategy from day one (e.g., /api/v1/...). Maintain backward compatibility for a specified period for non-breaking changes.