Document 04: Non-functional Requirements
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the Non-Functional Requirements (NFRs) for the SkyFi Networks ISP Management System. NFRs define the quality attributes, operational characteristics, and design constraints of the system. They describe how the system should behave, rather than what specific functions it should perform.

These requirements are critical for guiding architectural decisions, technology choices, and infrastructure planning. They ensure the final product is not only functionally complete but also performant, scalable, secure, reliable, and maintainable, meeting the standards of an enterprise-grade platform.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define, document, and own the NFRs. Ensure the system architecture adheres to these requirements.
Development Lead	Ensure the development team implements code and infrastructure that meets the NFRs.
QA Lead / Performance Engineer	Design and execute tests (e.g., load, stress, security) to verify that the system meets the specified NFRs.
Operations / DevOps	Provision and manage the infrastructure to support the NFRs, particularly availability, scalability, and disaster recovery.
Product Owner	Understand the business impact and cost/benefit trade-offs of NFRs and factor them into prioritization.
3.0 Goals
Establish Quality Standards: Define clear, measurable, and testable benchmarks for system quality.
Guide Architecture: Provide essential constraints that shape the software, system, and deployment architecture.
Mitigate Risk: Proactively address risks related to performance, security, scalability, and system failure.
Ensure User Satisfaction: Guarantee a responsive, reliable, and secure experience for all users (staff and customers).
Support Business Growth: Ensure the system can scale and evolve to meet the future needs of SkyFi Networks.
4.0 Non-Functional Requirements Specification
NFRs are categorized and detailed below. Each requirement includes a measurable metric to ensure testability and compliance.

4.1 Performance
ID	Requirement	Metric / Measurement	Context / Justification	Priority
NFR-PERF-001	API Response Time: All primary API endpoints (e.g., GET customer, GET invoice list) must respond under normal load.	95th percentile response time < 200ms. 99th percentile < 500ms.	Fast API responses are critical for a snappy frontend user experience and preventing user frustration.	[P1]
NFR-PERF-002	Server-Side Page Generation: For the initial server-rendered HTML before the React SPA loads.	Time To First Byte (TTFB) < 300ms.	A fast TTFB is crucial for perceived performance and SEO.	[P1]
NFR-PERF-003	Web UI Interactivity: Key user interface actions (e.g., opening a modal, filtering a table) must feel instantaneous.	Largest Contentful Paint (LCP) < 2.5s. First Input Delay (FID) < 100ms.	Meets Google's Core Web Vitals standards for a good user experience.	[P1]
NFR-PERF-004	Batch Job Processing: The monthly billing generation process for 10,000 customers must complete within a defined window.	Job completion time < 4 hours.	Ensures all invoices are generated and sent on time without impacting system performance during business hours.	[P1]
NFR-PERF-005	MikroTik API Latency: Communication latency for provisioning actions (e.g., create PPPoE user) must be handled gracefully.	Action completes or times out with a clear error within 15 seconds.	Network operations can be slow. The UI must not block, and clear feedback must be provided to the user.	[P2]
4.2 Scalability
ID	Requirement	Metric / Measurement	Context / Justification	Priority
NFR-SCAL-001	Concurrent User Load (Staff): The system must support a minimum number of concurrent internal staff users without performance degradation.	100 concurrent staff users performing standard operations (CRM, Billing, Support).	Supports medium-sized operations teams and regional growth without immediate re-architecture.	[P1]
NFR-SCAL-002	Concurrent User Load (Customers): The customer portal must support a high number of concurrent customer users, especially during billing cycles.	1,000 concurrent customer users performing portal actions (viewing/paying invoices).	High concurrency is expected on invoice issue day. The system must remain stable and responsive.	[P1]
NFR-SCAL-003	Horizontal Scalability: The application tier must be designed to scale horizontally by adding more web server instances.	Adding a new PHP application server must linearly increase concurrent user capacity by >75% of a single server's capacity.	This is the primary strategy for handling increased load. The application must be stateless.	[P1]
NFR-SCAL-004	Data Volume Growth: The system must perform according to NFR-PERF-001 with a substantial volume of data.	Up to 50,000 active subscribers, 5 million invoices, and 10 million payment records.	The database schema, queries, and indexing strategy must be designed to handle significant growth.	[P2]
NFR-SCAL-005	Network Device Scalability: The system must handle monitoring and management of a large fleet of network devices.	Up to 1,000 MikroTik routers managed by the NMS module.	Ensures the network management polling and job queues can scale with network expansion.	[P2]
4.3 Reliability & Availability
ID	Requirement	Metric / Measurement	Context / Justification	Priority
NFR-REL-001	System Uptime: The system must be highly available for both internal and external users.	99.9% uptime ("three nines") for all customer-facing and core business components.	This equates to a maximum of ~43 minutes of unplanned downtime per month. Critical for a 24/7 business.	[P1]
NFR-REL-002	Recovery Time Objective (RTO): In the event of a catastrophic failure, the time to restore service must be minimized.	RTO < 4 hours.	Defines the maximum acceptable duration for a system outage before it causes significant business damage.	[P1]
NFR-REL-003	Recovery Point Objective (RPO): In the event of a catastrophic failure, the amount of data loss must be minimized.	RPO < 1 hour.	Defines the maximum acceptable age of files that must be recovered from backup storage for normal operations to resume. Dictates backup frequency.	[P1]
NFR-REL-004	Fault Tolerance: The failure of a non-critical external service must not cause a full system outage.	If the mapping service or SMS gateway is down, the core CRM/Billing functions must remain operational.	The system should degrade gracefully, isolating failures to prevent cascading system-wide outages.	[P2]
4.4 Security
ID	Requirement	Metric / Measurement	Context / Justification	Priority
NFR-SEC-001	Data Encryption in Transit: All communication between the client, servers, and external services must be encrypted.	TLS 1.2+ enforced on all endpoints. A+ grade on SSL Labs test.	Prevents eavesdropping, man-in-the-middle attacks, and session hijacking.	[P1]
NFR-SEC-002	Data Encryption at Rest: All sensitive customer PII and financial data stored in the database must be encrypted.	Database fields containing PII (e.g., names, addresses) and configuration secrets must be encrypted.	Protects sensitive data in the event of a physical data breach or unauthorized database access.	[P1]
NFR-SEC-003	Vulnerability Protection: The application must be protected against the OWASP Top 10 web application security risks.	Zero critical or high-severity vulnerabilities identified during static analysis (SAST), dynamic analysis (DAST), and penetration testing.	Foundational requirement for any modern web application to prevent common attacks like XSS, CSRF, and SQL Injection.	[P1]
NFR-SEC-004	Authentication Security: User credentials must be stored securely.	Passwords must be hashed using a modern, strong, salted algorithm (e.g., Argon2id).	Prevents user passwords from being compromised even if the database is breached.	[P1]
NFR-SEC-005	Session Management: User sessions must have defined security controls.	JWTs must have a short expiry (e.g., 15 minutes) and be managed by a secure refresh token mechanism. Inactivity logout enforced after 30 minutes.	Mitigates the risk of token theft and unauthorized access from unattended workstations.	[P1]
4.5 Maintainability & Supportability
ID	Requirement	Metric / Measurement	Context / Justification	Priority
NFR-MAIN-001	Code Quality: The codebase must be clean, well-structured, and easy to understand.	Adherence to defined Coding Standards. Cyclomatic complexity < 10 for all methods/functions.	Reduces the cost of maintenance, simplifies bug fixing, and makes onboarding new developers easier.	[P1]
NFR-MAIN-002	Test Coverage: Core business logic must be covered by automated tests.	Unit test coverage > 80% for backend business logic modules (Billing, Provisioning). Integration test coverage for critical workflows.	Ensures new changes don't break existing functionality (regression), enabling faster and safer deployments.	[P1]
NFR-MAIN-003	Centralized Logging: All application and system logs must be centralized and searchable.	All logs aggregated to a central system (e.g., ELK Stack, Datadog) with structured formatting (JSON).	Essential for efficient troubleshooting, debugging, and security incident investigation.	[P1]
NFR-MAIN-004	API Documentation: The REST API must be comprehensively documented.	100% of public endpoints documented using the OpenAPI 3.0 specification.	Crucial for frontend developers, future mobile app teams, and any third-party integrators.	[P1]
NFR-MAIN-005	Health Checks: The application must expose health check endpoints for monitoring.	A /health endpoint that returns 200 OK if the application and its core dependencies (database) are healthy.	Allows load balancers and monitoring systems to automatically detect and route traffic away from unhealthy instances.	[P1]
4.6 Usability & Accessibility
ID	Requirement	Metric / Measurement	Context / Justification	Priority
NFR-USA-001	Responsive Design: The application must be usable on standard desktop screen sizes.	The UI must function correctly and be aesthetically acceptable on resolutions from 1366x768 up to 4K.	Staff use a variety of monitor sizes. The interface should not break on common resolutions.	[P1]
NFR-USA-002	Browser Compatibility: The application must work on modern, up-to-date web browsers.	Full support for the latest two major versions of Chrome, Firefox, Safari, and Edge.	Ensures a consistent experience for the vast majority of users without the overhead of supporting legacy browsers.	[P1]
NFR-USA-003	Accessibility: The application should be usable by people with disabilities.	Customer Portal and core staff workflows must aim for Web Content Accessibility Guidelines (WCAG) 2.1 Level AA compliance.	This is an ethical and often legal requirement. It also improves overall usability for everyone.	[P2]
4.7 Compliance
ID	Requirement	Metric / Measurement	Context / Justification	Priority
NFR-COMP-001	Payment Card Industry (PCI) Compliance: The system's handling of cardholder data must adhere to PCI DSS.	The system must be designed to be PCI DSS compliant, primarily by using a payment gateway tokenization strategy to avoid storing, processing, or transmitting raw cardholder data.	Mandatory for accepting credit card payments. Non-compliance carries severe financial and reputational penalties.	[P1]
NFR-COMP-002	Data Privacy: The system must be designed to support compliance with data privacy regulations.	Ability to fulfill data subject requests (e.g., access, deletion) as required by regulations like GDPR or CCPA.	Critical for operating in jurisdictions with strong privacy laws and building customer trust.	[P2]
5.0 Architectural Implications
These NFRs directly drive key architectural decisions:

mermaid

graph TD
    subgraph NFRs
        A[Scalability: NFR-SCAL-003]
        B[Reliability: NFR-REL-001]
        C[Security: NFR-SEC-001]
        D[Maintainability: NFR-MAIN-003]
        E[Performance: NFR-PERF-001]
    end

    subgraph Architecture Decisions
        A1[Stateless PHP Backend]
        B1[Redundant Load Balancers & App Servers]
        C1[HTTPS Everywhere, API Gateway]
        D1[Centralized Logging Infrastructure (ELK/Datadog)]
        E1[Database Indexing Strategy, Caching Layer (Redis)]
    end

    A --> A1
    B --> B1
    C --> C1
    D --> D1
    E --> E1

    A1 --> B1
    E1 --> B1
Scalability & Reliability (NFR-SCAL-003, NFR-REL-001) mandate a stateless application tier and a redundant, load-balanced deployment architecture.
Performance (NFR-PERF-001) necessitates a thoughtful database indexing strategy and the potential introduction of a caching layer (e.g., Redis) for frequently accessed, non-volatile data.
Security (NFR-SEC-001, NFR-SEC-005) demands that the architecture include TLS termination at the edge (load balancer or API gateway) and a robust JWT-based authentication/authorization flow.
Maintainability (NFR-MAIN-003, NFR-MAIN-005) requires that logging, monitoring, and health checks are not application add-ons but core parts of the system infrastructure.
6.0 Testing & Verification Strategy
Verifying NFRs requires specialized testing beyond standard functional QA:

Performance & Scalability: Load and stress testing will be performed using tools like JMeter or k6 to simulate concurrent user loads and measure response times, throughput, and resource utilization.
Reliability: Chaos engineering principles will be applied in a staging environment to test fault tolerance (e.g., killing a server instance, introducing network latency). Disaster recovery plans will be tested annually.
Security: Regular vulnerability scanning (SAST/DAST) will be integrated into the CI/CD pipeline. Third-party penetration testing will be conducted before initial launch and annually thereafter.
Maintainability: Code quality and test coverage metrics will be automatically tracked using tools like SonarQube. Pull requests will be blocked if they decrease coverage or violate quality gates.
7.0 Risks
Risk	Mitigation
Performance vs. Cost: Meeting aggressive performance NFRs can increase infrastructure costs.	NFRs are defined with business context. We will performance-tune bottlenecks identified by profiling, rather than pre-emptively over-optimizing.
Security vs. Usability: Excessive security measures can sometimes hinder usability (e.g., very short session timeouts).	A balanced approach will be taken, following industry best practices. Risk assessments will be conducted for any deviations.
"NFR Neglect": Development teams often focus on features (functional requirements) at the expense of NFRs.	NFRs are first-class requirements. The QA process will explicitly include NFR verification. User stories will contain NFR-related acceptance criteria where applicable.
Test Environment Disparity: Testing NFRs in an environment that doesn't mirror production can yield misleading results.	A dedicated, production-like staging environment will be maintained for performance and security testing.
