Document 08: Module Architecture
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the internal architecture for the business modules within the PHP backend (e.g., Billing, CRM, Network). It defines the standard layers, components, and communication patterns that must be used inside each module.

The goal is to ensure that all modules are built with a consistent internal structure, promoting code reuse, testability, and separation of concerns. This standardizes how developers build features, regardless of the specific business domain, which is crucial for long-term maintainability and team scalability.

2.0 Responsibilities
Role	Responsibility
Principal Architect	Define and own this module architecture. Ensure it aligns with the overall software architecture.
Development Lead	Enforce adherence to this layered architecture during code reviews. Mentor developers on these patterns.
Senior Developers	Act as champions for the architecture, providing examples and guidance for implementing complex business logic within this structure.
All Developers	Implement all business logic according to the layers and patterns defined in this document.
3.0 Goals
Enforce Separation of Concerns: Clearly separate business logic from framework/infrastructure concerns like HTTP requests and database queries.
Maximize Testability: Design modules so that core business logic can be unit-tested in isolation, without needing a web server or a database.
Promote Loose Coupling: Ensure that modules are internally cohesive and loosely coupled from other modules and the underlying framework.
Standardize Development: Provide a consistent "way of working" for all backend developers, making the codebase predictable and easier to reason about.
Isolate Domain Logic: Create a clear home for the complex rules and processes that define the business, protecting this valuable intellectual property from being scattered throughout the codebase.
4.0 Core Architectural Pattern: Layered Architecture within Modules
Each business module (e.g., src/Billing, src/Crm) will be internally structured using a Layered Architecture. This pattern organizes the code into horizontal layers, where each layer has a specific responsibility. Requests flow downwards through the layers, and data or results flow back upwards.

Architectural Justification: This is a proven, robust pattern for building enterprise applications. It directly supports the goals of separation of concerns and testability. By isolating the core business logic in a Service layer, we can test it independently of the Controller (HTTP) and Repository (Data) layers.

5.1 Module Layer Diagram
This diagram shows the standard layers within any given business module and the flow of control.

mermaid

graph TD
    subgraph "Framework Layer (External to Module)"
        Router[API Router]
    end

    subgraph "Module Boundary (e.g., /src/Billing)"
        subgraph "1. Controller Layer (Application/Framework Interface)"
            Controller[BillingController]
        end

        subgraph "2. Service Layer (Core Business Logic)"
            Service[BillingService]
        end

        subgraph "3. Repository Layer (Data Abstraction)"
            Repository[InvoiceRepository]
        end

        subgraph "4. Model/Entity Layer (Data Structure)"
            Model[(Invoice Model)]
        end
    end

    subgraph "Infrastructure Layer (External to Module)"
        Database[(MySQL Database)]
    end

    Router -- "HTTP Request (e.g., POST /invoices)" --> Controller
    Controller -- "Calls Method (e.g., createInvoice(data))" --> Service
    Service -- "Performs Logic, then Requests/Persists Data" --> Repository
    Repository -- "Uses Model to Interact with DB" --> Model
    Repository -- "SQL Query" --> Database

    Database -- "Returns Rows" --> Repository
    Repository -- "Hydrates Model(s)" --> Service
    Service -- "Returns Result/DTO" --> Controller
    Controller -- "Returns HTTP Response (e.g., JSON)" --> Router
5.2 Layer Responsibilities
Controller Layer (src/Billing/Controllers/)

Responsibility: To be the entry point for a user/client request. Its only job is to handle the HTTP interaction.
Tasks:
Parse the incoming HTTP Request (headers, body, query parameters).
Perform initial validation on the request data using Form Request validation or similar.
Call the appropriate method on a Service layer class, passing in validated data (often as a Data Transfer Object - DTO).
Receive the result from the Service.
Format the result into a standardized HTTP Response (e.g., a JSON API Resource).
Handle HTTP-specific exceptions and return appropriate status codes (e.g., 404, 403).
Forbidden: Controllers must not contain any business logic. They must not interact directly with database Models or Repositories.
Service Layer (src/Billing/Services/)

Responsibility: To contain all the core business logic and orchestrate the application's use cases. This is the heart of the module.
Tasks:
Execute complex business rules and workflows (e.g., "To generate a monthly invoice, get the customer's active services, calculate the total, apply any credits, save the invoice, and dispatch an event").
Coordinate interactions between different Repositories (and sometimes, other Services).
Dispatch domain events (e.g., InvoiceGenerated).
Perform complex calculations.
Make decisions based on the state of the domain.
Forbidden: Services must not have any knowledge of the HTTP layer (no Request or Response objects). They should be pure PHP classes that could, in theory, be run from a command-line script as easily as from a web request.
Repository Layer (src/Billing/Repositories/)

Responsibility: To abstract the data persistence mechanism. It acts as an in-memory collection of domain objects.
Architectural Justification (Repository Pattern): This pattern decouples the business logic (Service) from the data access logic (SQL queries). This allows us to change the persistence layer (e.g., switch to a NoSQL DB, add caching) with minimal impact on the Service layer. It also makes testing easier, as we can provide a "fake" in-memory repository for unit tests.
Tasks:
Provide methods for retrieving and persisting data (e.g., findById, findAllForCustomer, save, update).
Contain all the SQL queries (or ORM calls).
Handle data hydration (turning raw database rows into rich Model objects).
Forbidden: Repositories must not contain business logic. Their methods should be focused on data retrieval and storage.
Model/Entity Layer (src/Billing/Models/)

Responsibility: To represent the data structures and their relationships within the domain.
Tasks:
Define the object's properties (e.g., an Invoice has an id, customer_id, total_amount, status).
Define relationships to other models (e.g., an Invoice belongsTo a Customer).
May contain very simple, intrinsic logic related to the object's own state (e.g., a method isPaid() that checks if status === 'paid').
Forbidden: Models must not be responsible for saving themselves or fetching data from the database. That is the Repository's job. This is a key distinction from the Active Record pattern, which we are intentionally avoiding to maintain separation of concerns.
6.0 Communication and Dependencies
Dependency Injection (DI): Dependencies between layers will be managed via a DI Container. For example, the BillingService will have the InvoiceRepository "injected" into its constructor. This is crucial for loose coupling and testability.

Example:

PHP

// In BillingService.php
class BillingService {
    public function __construct(
        private InvoiceRepositoryContract $invoiceRepository,
        private CustomerRepositoryContract $customerRepository
    ) {}

    public function generateMonthlyInvoices() {
        // ... uses the injected repositories
    }
}
Contracts (Interfaces): All Services and Repositories will be backed by an interface (a Contract). The DI container will bind the interface to a concrete implementation. This allows us to swap implementations easily.

Example:

PHP

// In src/Billing/Contracts/InvoiceRepositoryContract.php
interface InvoiceRepositoryContract {
    public function find(int $id): ?Invoice;
    public function save(Invoice $invoice): bool;
}

// In src/Billing/Repositories/MySqlInvoiceRepository.php
class MySqlInvoiceRepository implements InvoiceRepositoryContract {
    // ... concrete implementation using MySQL
}
Data Transfer Objects (DTOs): DTOs (src/Billing/Data/) are simple, immutable objects used to transfer structured data between layers, especially from Controllers to Services. This prevents passing around large, messy arrays or the raw HTTP Request object, creating a clear and strict contract for what data a service method requires.

7.0 Inter-Module Communication
Modules must remain as independent as possible. Direct communication between modules is a carefully controlled process.

Communication Type	Method	When to Use	Example
Synchronous (Service-to-Service Call)	A service in one module (e.g., BillingService) directly calls a public method on a service from another module (e.g., NetworkService) via its interface.	When an immediate response is required for the current workflow to continue.	The BillingService's suspendOverdueCustomer method needs to call the NetworkService's disablePppoeUser method and wait for confirmation.
Asynchronous (Event-Driven)	A service dispatches a domain event. A listener in another module (or the same module) reacts to that event.	When the action does not need to happen immediately, or when one action needs to trigger multiple, independent side effects. This is the preferred method for decoupling.	The BillingService dispatches an InvoiceGenerated event. The NotificationService (in the Shared module) listens for this and sends an email. A future ReportingService could also listen for it to update a daily sales report. The BillingService doesn't know or care who is listening.
Inter-Module Communication Diagram:

mermaid

graph TD
    subgraph Billing Module
        BS[BillingService]
        E[Dispatches InvoicePaid Event]
    end
    
    subgraph Network Module
        NS[NetworkService]
    end
    
    subgraph Support Module
        SL[SupportTicketListener]
    end
    
    BS -- Synchronous Call --> NS[NetworkService.reactivateUser()]
    BS --> E
    E -- Asynchronous --> SL[SupportTicketListener.closeOverdueTicket()]

    style E fill:#cde4ff
Justification: Favor asynchronous, event-driven communication where possible. It dramatically reduces coupling between modules. The Billing module shouldn't need to know about the internal workings of the Support module. It simply announces that an invoice was paid.

8.0 Risks
Risk	Description	Mitigation Strategy
Leaky Abstractions	Business logic "leaks" from the Service layer into Controllers or Repositories.	Vigilant code reviews are essential. The mantra is "Fat Services, Thin Controllers, Simple Repositories." Any complex if/else logic in a Controller is a red flag.
Anemic Domain Models	Models become simple property bags with no behavior, and all logic lives in Services (a "Transaction Script" pattern).	This is a trade-off. For this architecture, we are deliberately choosing a more procedural Service Layer over a rich Domain Model to simplify development and lower the learning curve for the team. Simple methods on models (isPaid()) are encouraged, but complex state transitions will reside in Services.
Interface Bloat	Creating interfaces for every single class can become tedious.	Be pragmatic. Interfaces are mandatory for Services and Repositories. For smaller, internal helper classes, they may not be necessary. The key is to use interfaces at the boundaries between layers and modules.
9.0 Enterprise Recommendations
Standardization is Key: The value of this architecture comes from its consistent application. The first few features should be developed with heavy involvement from the Architect and Lead Developer to establish a strong precedent.
Generate Boilerplate: Create command-line tools to scaffold all the files and folders for a new resource within a module (e.g., php artisan make:resource Crm/Customer). This reduces friction and ensures the pattern is followed correctly.
Test by Layer:
Unit Tests: Focus on the Service Layer. Mock its repository dependencies to test the business logic in complete isolation.
Feature/Integration Tests: Test the Controller Layer. Make a real HTTP request to an endpoint and assert that the correct response is returned and the database was changed as expected. This tests the integration of all layers.