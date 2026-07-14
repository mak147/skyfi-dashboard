Document 43: Audit Logging
Project: SkyFi Networks ISP Management System
Document Version: 1.0
Date: 2023-10-27
Author: Principal Enterprise Software Architect
Status: Initial Draft

1.0 Purpose
This document specifies the architecture for the Audit Logging system. This system is responsible for creating a detailed, immutable, and searchable trail of all significant actions performed by users and the system itself.

The goal is to design a robust audit logging framework that:

Enhances security by tracking all sensitive actions.
Ensures accountability by attributing every change to a specific user.
Aids in forensic analysis during security incident investigations.
Fulfills compliance requirements for data governance and change management.
Provides transparency to administrators about system activity.
2.0 Responsibilities
Role	Responsibility
Principal Architect	Design the audit log data model, the event capturing mechanism, and the storage architecture.
Backend Developers	Integrate the audit logging logic into the application's core services and models using events and traits.
Frontend Developers	Build the user interface for viewing and searching the audit log.
Security & Compliance Officers	Primary Stakeholders. Define which events are considered "auditable" and specify the required data retention policies.
3.0 Core Principles
Immutability: Once an audit log entry is written, it must not be modified or deleted by any application user, including Super Administrators.
Asynchronicity: The act of writing an audit log should not block the primary user action. Audit events will be dispatched to a dedicated queue to be processed by a background worker. This ensures that logging does not impact application performance.
Attribution: Every log entry must be clearly attributed to the user who initiated the action. For system-automated actions, attribution should be to a designated "System" user.
Context is Key: A log entry is useless without context. It must record not only that an action happened but also what changed (the "before" and "after" state).
Separation: The audit log data will be stored in a dedicated table, separate from operational data, and potentially in a separate, more secure database or log store in the future.
4.0 What to Log: Auditable Events
Not every action needs to be audited. We will focus on significant events related to security, financial data, and configuration changes.

Categories of Auditable Events:

Authentication & Authorization:
User login success/failure.
User logs out.
Password reset requested/completed.
User's roles or permissions changed.
New user created; user deactivated.
Billing & Finance:
Invoice created, updated (e.g., voided), or deleted.
Payment created or refunded.
Credit issued to a customer account.
Service plan created, price changed, or deleted.
Customer & Service Management:
Customer record created or deleted.
Customer status changed (e.g., suspended, reactivated).
Service provisioned, suspended, or terminated.
System & Security Configuration:
System setting changed (e.g., dunning rules).
Payment gateway configuration updated.
New tower or router added.
5.0 Architecture: Event-Driven and Centralized
We will use an event-driven architecture, similar to the Notification and Finance systems, to capture audit events in a decoupled manner.

Architecture Diagram:

mermaid

graph TD
    subgraph "Application Core"
        A[Model Events (e.g., on `Customer` update)]
        B[Service Logic (e.g., `loginUser` method)]
    end

    subgraph "Audit System"
        C(Dispatches Generic `AuditableEventOccurred`)
        D{Audit Log Queue}
        E[AuditLogWriter Job]
    end

    subgraph "Data Store"
        F[(audit_logs Table)]
    end
    
    A --> C
    B --> C
    C --> D
    E -- Dequeues from --> D
    E -- Writes to --> F
Implementation Strategy:

Model Observers/Traits (for CRUD events): The easiest way to capture changes to data models (Create, Update, Delete) is to use an automated system. We will create a PHP trait (e.g., Auditable) that can be added to any data model we want to audit (e.g., Customer, Invoice, ServicePlan).
This trait will automatically listen for model events (created, updated, deleted).
When an event fires, the trait will automatically capture the old and new values of the model's attributes.
It will then dispatch a generic AuditableEventOccurred with all the necessary context.
Manual Dispatch (for non-CRUD events): For actions that aren't simple model changes (e.g., user login, report exported), the service logic will manually dispatch the AuditableEventOccurred.
Centralized AuditableEventOccurred: This single event class will carry a standardized payload:
user_id: The actor.
event_name: A clear name for the action (e.g., customer.updated).
auditable_type, auditable_id: Polymorphic relation to the object that was acted upon.
old_values, new_values: JSON objects representing the changed data.
ip_address, user_agent: Context about the request.
Queued Listener: A listener for AuditableEventOccurred will do one simple thing: push a job onto a dedicated audit queue.
AuditLogWriter Job: A background worker processes jobs from the audit queue and writes the final, formatted record to the audit_logs database table.
Justification: This architecture is highly decoupled and performant. The business logic in a service simply fires an event and moves on. The complexity of formatting and storing the log is handled asynchronously in the background. The Auditable trait provides a huge developer experience win by automating the most common audit logging scenarios.

6.0 Data Model (audit_logs Table)
This table is designed for detailed forensic analysis and is append-only.

Column	Type	Description
id	UUID	PK: A non-sequential unique ID.
user_id	INT	FK (Nullable): The user who performed the action. NULL for system actions.
event_name	VARCHAR(255)	The machine-readable name of the event (e.g., invoice.created).
auditable_type	VARCHAR(255)	The class name of the target model (e.g., App\Models\Invoice).
auditable_id	BIGINT	The ID of the target model instance.
old_values	JSON	A JSON object containing the model attributes before the change.
new_values	JSON	A JSON object containing the model attributes after the change.
url	TEXT	The full URL of the request that triggered the event.
ip_address	VARCHAR(45)	The IP address of the actor.
user_agent	TEXT	The user agent string of the actor's browser/client.
created_at	TIMESTAMP	The exact time the event occurred.
7.0 User Interface
URL: /admin/audit-log
Access: Restricted to Super Administrators or users with a specific view:audit-log permission.
Layout: A powerful data table interface designed for searching and filtering.
Filtering: Users must be able to filter the log by:
Date Range (most important).
User (the actor).
Event Name (e.g., show all customer.suspended events).
A specific resource (e.g., show the complete history for Invoice #123).
Detail View: Clicking on a log entry will open a modal or side panel that shows a clean "diff" view of the old_values and new_values, clearly highlighting what was added, changed, and removed.
Diff View Example:

Field	Old Value	New Value
status	pending_approval	approved
notes	Please review.	Approved for purchase.
8.0 Security and Storage
Data Integrity: The audit_logs table should have database-level permissions that prevent UPDATE or DELETE operations by the application's primary database user. This enforces immutability at the infrastructure level.
Log Retention: A clear data retention policy must be defined based on compliance and business needs (e.g., "retain all audit logs for 7 years").
Long-Term Storage (Archiving): As the audit_logs table can grow to be one of the largest in the system, an archiving strategy is essential.
A nightly or weekly job will move logs older than a certain threshold (e.g., 6 months) from the primary MySQL database to a cheaper, long-term cold storage solution like Amazon S3 Glacier or a dedicated data warehouse.
The application UI would then need to be able to query both the "hot" data in MySQL and the "cold" data in the archive, or direct users to a separate interface for historical searches.
9.0 Risks
Risk	Description	Mitigation Strategy
Missing Audit Events	A developer adds a new sensitive feature but forgets to implement the audit trail.	The Auditable trait helps automate this for most CRUD actions. The code review checklist for any PR involving data mutation must include the question: "Does this action need to be audited?". A list of all auditable events should be maintained and reviewed.
Performance Degradation	The updated event on a frequently-changed model (e.g., a real-time status object) generates a flood of audit logs, overwhelming the queue and database.	Be selective about which models use the Auditable trait. For high-frequency models, auditing might be disabled or configured to only log specific, important attribute changes rather than every minor update.
Loss of Audit Data	The audit queue fails, or the background worker crashes, causing audit events to be lost.	The message queue must be configured for persistence (e.g., Redis with AOF or a more durable queue like SQS). The AuditLogWriter job must have a robust "failed job" handling mechanism to retry writing logs that failed. The queue health must be monitored.
Contextual Gaps	An audit log shows that a service status was changed to 'suspended' but doesn't explain why (e.g., was it an automatic dunning process or a manual admin action?).	The event_name should be specific. Instead of a generic service.updated, we should have service.suspended.dunning and service.suspended.manual. This provides richer context directly in the log entry.
